import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

/**
 * Load `.claude/.env` (gitignored) into process.env so `npm run e2e` works without
 * sourcing it by hand. Keys read elsewhere BY NAME: WP_BASE_URL, WP_PATH_PREFIX,
 * WP_ADMIN_USER, WP_ADMIN_PASS and WP_PLUGIN_PATH.
 *
 * Already-set values win, so CI / explicit overrides still apply. No dotenv
 * dependency — the file is plain KEY=VALUE lines.
 */
export function loadEnv() {
  const repoRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..', '..')
  const envFile = path.join(repoRoot, '.claude', '.env')
  if (!fs.existsSync(envFile)) return

  for (const line of fs.readFileSync(envFile, 'utf8').split('\n')) {
    const trimmed = line.trim()
    const eq = line.indexOf('=')
    if (!trimmed || trimmed.startsWith('#') || eq === -1) continue
    const key = line.slice(0, eq).trim()
    if (!/^[A-Z_][A-Z0-9_]*$/.test(key) || process.env[key] !== undefined) continue
    process.env[key] = line.slice(eq + 1).trim()
  }
}
