#!/usr/bin/env bash
#
# Check that every source file in the docblock-tagged tiers declares at least one
# @since tag. This guards the @since/@version provenance convention: a new PHP
# type, view/bootstrap file header, or JS source module that ships without
# provenance fails loud instead of drifting untagged.
#
# Tagged tiers (each tracked file MUST contain @since):
#   PHP: foundation/src, modules/admin-ui/src, src, the two root bootstrap files
#        (plugin main file + uninstall.php), and the resources/views templates.
#   JS:  the resources/js source modules.
#
# Out of scope BY DESIGN (not checked): tests/, build configs (vite.config.js,
# playwright.config.js), and anything under vendor/ or node_modules/.
#
# Presence only — it does not police the version value; that a tag cites the
# correct version is verified during release review, not here.
#
# Usage:
#   bin/check-since-tags.sh

set -euo pipefail

# Run from the repo root regardless of where the script is invoked from.
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

# git pathspecs (the '*' spans directories) enumerate only TRACKED files in the
# tagged tiers, so tests/ and build configs are excluded by simply not listing
# them. The two root bootstrap files are named explicitly — the same pair
# phpcs.xml.dist lists — and paths are repo-relative, so the CI checkout's
# directory name never matters.
fail=0
checked=0
missing=0

while IFS= read -r file; do
  checked=$((checked + 1))
  if ! grep -q '@since' "$file"; then
    printf '  MISSING @since  %s\n' "$file"
    missing=$((missing + 1))
    fail=1
  fi
done < <(
  git ls-files \
    'foundation/src/*.php' \
    'modules/admin-ui/src/*.php' \
    'src/*.php' \
    'resources/views/*.php' \
    'resources/js/*.js' \
    'resources/js/*.ts' \
    'resources/js/*.tsx' \
    'woocommerce-parcelas.php' \
    'uninstall.php'
)

if [ "$checked" -eq 0 ]; then
  echo "ERROR: no files matched the tagged tiers — run from a git checkout." >&2
  exit 1
fi

if [ "$fail" -ne 0 ]; then
  echo "FAILED: $missing of $checked tagged-tier file(s) declare no @since." >&2
  exit 1
fi

echo "OK: all $checked tagged-tier source files declare @since."
