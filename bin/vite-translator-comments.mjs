/**
 * Keeps each `translators:` comment in the built bundle, where `wp i18n make-pot`
 * reads it. The minifier keeps only legal comments (the `/*!` kind), and make-pot
 * skips those, so the comment turns legal before the build and back after it.
 */
const TRANSLATORS = /\/\*\s*translators:/gi
const LEGAL_TRANSLATORS = /\/\*! translators:/g

export function translatorComments() {
  return {
    name: 'translator-comments',
    apply: 'build',
    // Ahead of the TypeScript/JSX transform, which drops ordinary comments.
    enforce: 'pre',
    transform(code, id) {
      if (!/\.[cm]?[jt]sx?$/.test(id) || id.includes('/node_modules/')) return null
      const legal = code.replace(TRANSLATORS, '/*! translators:')
      return legal === code ? null : legal
    },
    outputOptions(options) {
      // GOTCHA: this also keeps the bundled dependencies' license notices.
      return { ...options, comments: { legal: true, annotation: false, jsdoc: false } }
    },
    generateBundle(_, bundle) {
      for (const chunk of Object.values(bundle)) {
        if (chunk.type === 'chunk') chunk.code = chunk.code.replace(LEGAL_TRANSLATORS, '/* translators:')
      }
    },
  }
}
