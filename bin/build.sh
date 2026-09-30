#!/usr/bin/env bash
#
# Build the distributable plugin ZIP — the artifact shipped to wordpress.org.
#
# Order matters; this script enforces it:
#   1. Compile front-end assets    (npm ci && npm run build  -> public/build/, languages/ .pot + .mo + .json)
#   2. Install production deps      (composer install --no-dev -> vendor/)
#   3. Package the ZIP              (rsync the tree minus .distignore -> dist/<slug>-<version>.zip)
#   4. Restore dev deps             (composer install)  unless --no-restore
#
# Packaging is done with rsync + zip (not `wp dist-archive`) so the build is
# self-contained and runs the same locally and in CI. npm run build also compiles
# the translations (bin/i18n.sh build), which needs WP-CLI.
# .distignore stays the single source of truth for what is excluded; this script
# feeds every entry to rsync as an --exclude.
#
# Requirements on PATH: npm, wp (WP-CLI), composer, rsync, zip.
#
# Usage:
#   bin/build.sh               # build, then restore dev dependencies
#   bin/build.sh --no-restore  # build, leave vendor/ at production (--no-dev) state

set -euo pipefail

# Run from the repo root regardless of where the script is invoked from.
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

# Slug = the repo directory name (also the plugin folder name and the ZIP basename).
# Single-sourced from the directory so build.sh and plugin-check.sh can never disagree.
slug="$(basename "$repo_root")"
# Main file found by CONTENT (the top-level .php with a Plugin Name header), not
# by directory name — the checkout dir may differ from the slug (as in CI), and
# the main file may not be <slug>.php (the contract's plugin_main_file exists
# precisely because the two can diverge).
main_file="$(grep -lE '^[[:space:]]*\*?[[:space:]]*Plugin Name:' ./*.php 2>/dev/null | head -1 || true)"
main_file="${main_file#./}"
[ -n "$main_file" ] || { echo "ERROR: no top-level PHP file with a 'Plugin Name:' header" >&2; exit 1; }
restore=1

for arg in "$@"; do
  case "$arg" in
    --no-restore) restore=0 ;;
    -h|--help)
      cat <<'USAGE'
Build the distributable plugin ZIP (dist/<slug>-<version>.zip).

Steps: npm ci && npm run build  ->  composer install --no-dev  ->  rsync + zip.
Excludes are read from .distignore. Requires npm, wp (WP-CLI), composer, rsync, zip on PATH.

Usage:
  bin/build.sh               build, then restore dev dependencies
  bin/build.sh --no-restore  build, leave vendor/ at production (--no-dev) state
USAGE
      exit 0 ;;
    *) echo "Unknown option: $arg (try --help)" >&2; exit 2 ;;
  esac
done

# Version is single-sourced from the plugin header — never hardcode it here.
version="$(grep -iE '^[[:space:]]*\*?[[:space:]]*Version:' "$main_file" | head -1 | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
[ -n "$version" ] || { echo "ERROR: could not read 'Version:' from $main_file" >&2; exit 1; }

echo "==> Building $slug v$version"

# Preconditions.
for cmd in npm wp composer rsync zip; do
  command -v "$cmd" >/dev/null 2>&1 || { echo "ERROR: required command not found on PATH: $cmd" >&2; exit 1; }
done

# 1. Front-end assets.
echo "==> [1/4] Building front-end assets"
npm ci
npm run build
test -f public/build/.vite/manifest.json || { echo "ERROR: asset build produced no manifest" >&2; exit 1; }

# 2. Production Composer autoloader (runtime deps only).
echo "==> [2/4] Installing production Composer dependencies (--no-dev)"
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Package. Stage the tree (minus .distignore entries) under a folder named after
#    the slug so the ZIP extracts to wp-content/plugins/<slug>/, then zip it.
echo "==> [3/4] Packaging ZIP"
mkdir -p dist
zip_path="$repo_root/dist/$slug-$version.zip"
rm -f "$zip_path"

staging="$(mktemp -d)"
trap 'rm -rf "$staging"' EXIT
dest="$staging/$slug"
mkdir -p "$dest"

# Turn each .distignore line (comments + blanks skipped) into an rsync --exclude.
exclude_args=()
while IFS= read -r line || [ -n "$line" ]; do
  line="${line#"${line%%[![:space:]]*}"}"   # ltrim
  line="${line%"${line##*[![:space:]]}"}"    # rtrim
  [ -n "$line" ] || continue
  case "$line" in \#*) continue ;; esac
  exclude_args+=( --exclude="$line" )
done < .distignore

rsync -a "${exclude_args[@]}" ./ "$dest/"

( cd "$staging" && zip -rqX "$zip_path" "$slug" )

# 4. Restore the dev toolchain so the working tree is dev-ready again.
if [ "$restore" -eq 1 ]; then
  echo "==> [4/4] Restoring dev Composer dependencies"
  composer install --no-interaction
else
  echo "==> [4/4] Skipped dev-dependency restore (--no-restore); run 'composer install' to restore tooling"
fi

echo "==> Done: dist/$slug-$version.zip"
ls -lh "$zip_path"
