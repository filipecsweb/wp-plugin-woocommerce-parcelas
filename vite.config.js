import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import { cn } from 'cn/vite'
import path from 'node:path'

const WP_EXTERNAL = '\0wp-external:'
// [window global, core script handle] for the vendor packages core ships.
const VENDOR_EXTERNALS = {
  react: ['React', 'react'],
  'react-dom': ['ReactDOM', 'react-dom'],
  'react/jsx-runtime': ['ReactJSXRuntime', 'react-jsx-runtime'],
}
// @wordpress packages core does not expose as a wp.* global, so they stay bundled.
const BUNDLED_WP_PACKAGES = new Set([
  '@wordpress/icons',
  '@wordpress/dataviews',
  '@wordpress/interface',
  '@wordpress/sync',
  '@wordpress/undo-manager',
  '@wordpress/fields',
])

function wpExternal(id) {
  if (VENDOR_EXTERNALS[id]) return VENDOR_EXTERNALS[id]
  const match = /^@wordpress\/([^/]+)$/.exec(id)
  if (!match || BUNDLED_WP_PACKAGES.has(id)) return null
  return ['wp.' + match[1].replace(/-([a-z])/g, (_, c) => c.toUpperCase()), 'wp-' + match[1]]
}

/**
 * Reads React and @wordpress/* from the globals core already loads instead of
 * bundling copies, and writes .vite/wp-deps.json (manifest entry → core script
 * handles), which the Foundation's Vite enqueuer adds to the entry's dependencies.
 */
function wpExternals() {
  let root
  return {
    name: 'installment-prices-for-woocommerce-wp-externals',
    // Build only: under Vitest, React must resolve to node_modules, not to window.React.
    apply: 'build',
    enforce: 'pre',
    configResolved(config) {
      root = config.root
    },
    resolveId(id) {
      return wpExternal(id) ? WP_EXTERNAL + id : null
    },
    load(id) {
      if (!id.startsWith(WP_EXTERNAL)) return null
      // CJS on purpose: Rolldown can't derive named ESM exports from a global (MISSING_EXPORT).
      return `module.exports = window.${wpExternal(id.slice(WP_EXTERNAL.length))[0]};`
    },
    generateBundle(_, bundle) {
      // GOTCHA: an external shared by two entries lands in a common chunk, so walk static imports.
      const handles = (fileName, seen = new Set()) => {
        if (seen.has(fileName) || !bundle[fileName]) return []
        seen.add(fileName)
        const chunk = bundle[fileName]
        return [
          ...chunk.moduleIds.filter((id) => id.startsWith(WP_EXTERNAL)).map((id) => wpExternal(id.slice(WP_EXTERNAL.length))[1]),
          ...chunk.imports.flatMap((imported) => handles(imported, seen)),
        ]
      }
      const deps = {}
      for (const chunk of Object.values(bundle)) {
        if (chunk.type !== 'chunk' || !chunk.isEntry || !chunk.facadeModuleId) continue
        const entryDeps = [...new Set(handles(chunk.fileName))]
        if (entryDeps.length) deps[path.relative(root, chunk.facadeModuleId)] = entryDeps
      }
      if (Object.keys(deps).length) {
        this.emitFile({ type: 'asset', fileName: '.vite/wp-deps.json', source: JSON.stringify(deps, null, 2) })
      }
    },
  }
}

export default defineConfig({
  base: './',
  plugins: [
    tailwindcss(),
    cn({ content: ['resources/js/**/*.{ts,tsx}'], config: 'resources/js/ui/cn.config.mjs', out: 'resources/js/ui/cn-tables.js' }),
    wpExternals(),
  ],
  // The "@/…" imports: tsconfig.json's `paths` is their one definition.
  resolve: { tsconfigPaths: true },
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rolldownOptions: {
      input: 'resources/js/app/main.tsx',
      // Unhashed: WordPress finds a script's JSON translations by the md5 of its path. ?ver= busts caches.
      output: {
        entryFileNames: '[name].js',
        assetFileNames: '[name][extname]',
      },
      onLog(level, log, handler) {
        // Base UI and shadcn ship 'use client'; it means nothing outside React Server Components.
        if (log.code === 'MODULE_LEVEL_DIRECTIVE' && log.message.includes('"use client"')) return
        handler(level, log)
      },
    },
  },
  test: {
    include: ['tests/js/**/*.test.{ts,tsx}'],
    environment: 'jsdom',
  },
})
