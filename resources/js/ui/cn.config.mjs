/**
 * Build-time config for `cn build` (the `cn` plugin in vite.config.js): the tables it compiles
 * know the `tw` prefix and this theme's utility names.
 * WHY: unconfigured, a custom size such as `text-button` reads as a colour, so merging it
 * with `text-primary` silently drops one of the two.
 *
 * The names are read from the first @theme inline block of resources/css/app.css, the one
 * place they are defined; tests/js/ui/utils.test.ts checks each one merges in its group.
 *
 * @since 2.0.0
 */
import { readFileSync } from 'node:fs'
import { join } from 'node:path'

const css = readFileSync(join(import.meta.dirname, '../../css/app.css'), 'utf8')
const start = css.indexOf('@theme inline {')
const theme = css.slice(start, css.indexOf('\n}', start))
/** @type {(namespace: string) => string[]} */
export const themeNames = (namespace) =>
  [...theme.matchAll(new RegExp(`^\\s*--${namespace}-([a-z0-9-]+):`, 'gm'))].map((m) => m[1]).filter((name) => !name.includes('--'))

export default {
  prefix: 'tw',
  extend: {
    theme: {
      text: themeNames('text'),
      shadow: themeNames('shadow'),
      radius: themeNames('radius'),
    },
  },
}
