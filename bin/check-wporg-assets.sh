#!/usr/bin/env bash
#
# Check that .wordpress-org/assets/ holds only files the wp.org plugin page reads.
# deploy.yml mirrors that folder into SVN assets/ verbatim, so any other file (a
# README, a .gitkeep) is published on wp.org with the next release.
#
# Allowed, per developer.wordpress.org/plugins/wordpress-org/plugin-assets/ and
# developer.wordpress.org/plugins/wordpress-org/previews-and-blueprints/:
#   - top-level .png, .jpg, .gif or .svg files: banners, icons, screenshots and
#     their RTL/locale variants. Names are not policed, since wp.org's variants
#     (e.g. banner-772x250-es_ES.png) would make an exact list drift;
#   - blueprints/blueprint.json, the Playground preview.
#
# Tracked files only: CI deploys a checkout, never a working copy's untracked files.
#
# Usage:
#   bin/check-wporg-assets.sh

set -euo pipefail

# Run from the repo root regardless of where the script is invoked from.
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

# Must match the ASSETS_DIR deploy.yml hands the 10up action.
ASSETS_DIR=".wordpress-org/assets"
ALLOWED='^[^/]+\.(png|jpg|gif|svg)$|^blueprints/blueprint\.json$'

stray="$(git ls-files -- "$ASSETS_DIR" | sed "s|^$ASSETS_DIR/||" | grep -vE "$ALLOWED" || true)"

if [ -n "$stray" ]; then
  echo "FAIL: $ASSETS_DIR holds files wp.org does not read; the next deploy would publish them:" >&2
  sed 's/^/  /' <<<"$stray" >&2
  exit 1
fi
echo "OK: $ASSETS_DIR holds only wp.org listing files."
