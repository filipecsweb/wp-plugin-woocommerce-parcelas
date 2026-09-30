import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { loadEnv } from './support/load-env.js'

/**
 * E2E preflight: the WordPress under test must serve THIS checkout.
 *
 * Otherwise the suite silently validates a stale or duplicate copy of the plugin (a
 * real debugging trap — two .test sites each had their own copy). When
 * WP_PLUGIN_PATH is set, assert it resolves to this repo and fail loudly if not.
 * When it is unset (e.g. a site whose plugin dir is a plain copy rather than a
 * symlink to this checkout), warn rather than block.
 */
export default function globalSetup() {
  loadEnv() // defensive — the config already loads it, but make setup self-contained.

  const repoRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..')
  const target = process.env.WP_BASE_URL || '(WP_BASE_URL unset)'
  const pluginPath = process.env.WP_PLUGIN_PATH

  if (!pluginPath) {
    console.warn(`[e2e] WP_PLUGIN_PATH unset — skipping the "served plugin == this checkout" check. Target: ${target}`)
    return
  }

  let resolved
  try {
    resolved = fs.realpathSync(pluginPath)
  } catch {
    throw new Error(
      `[e2e] WP_PLUGIN_PATH does not exist: ${pluginPath}\n` +
        `The WordPress under test (${target}) must serve this plugin. ` +
        `Fix the path or the symlink before running e2e.`
    )
  }

  const repoReal = fs.realpathSync(repoRoot)
  if (resolved !== repoReal) {
    throw new Error(
      `[e2e] The plugin served by ${target} is NOT this checkout — e2e would test stale code.\n` +
        `  WP_PLUGIN_PATH resolves to: ${resolved}\n` +
        `  this checkout is:           ${repoReal}\n` +
        `Symlink the plugin to this repo (or update WP_PLUGIN_PATH) before running e2e.`
    )
  }

  console.log(`[e2e] OK — ${target} serves this checkout (${repoReal}).`)
}
