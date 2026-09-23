#!/usr/bin/env bash
#
# Check that the minimum PHP and minimum WordPress versions agree everywhere
# they are declared. The plugin header is the reference; every other
# declaration is compared against it:
#
#   min PHP: readme.txt, composer.json (require floor + config.platform),
#            phpcs testVersion, phpstan phpVersion, the ci.yml PHP matrix floor
#   min WP:  readme.txt, phpcs minimum_wp_version, composer wordpress-stubs floor
#
# Prints every declared value and exits non-zero if any of them disagree, so a
# bump that misses a copy fails loud instead of drifting.
#
# Usage:
#   bin/check-version-consistency.sh
#   bin/check-version-consistency.sh --wp-min   # print the header's minimum WordPress and stop

set -euo pipefail

# Run from the repo root regardless of where the script is invoked from.
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

# The main plugin file is the one top-level PHP file with a plugin header —
# found by content, NOT by directory name: CI checks out into the repository
# name (wp-plugin-…), which differs from the plugin slug the local dir uses.
main_file="$(grep -lE '^[[:space:]]*\*?[[:space:]]*Plugin Name:' ./*.php 2>/dev/null | head -1 || true)"
main_file="${main_file#./}"
[ -n "$main_file" ] || { echo "ERROR: no top-level PHP file with a 'Plugin Name:' header" >&2; exit 1; }

# The tools load the local override when present; check the file actually used.
phpcs_file=phpcs.xml
[ -f "$phpcs_file" ] || phpcs_file=phpcs.xml.dist
phpstan_file=phpstan.neon
[ -f "$phpstan_file" ] || phpstan_file=phpstan.neon.dist
ci_file=.github/workflows/ci.yml

fail=0

check() { # check <label> <actual> <expected>
  if [ "$2" = "$3" ]; then
    printf '  ok        %-42s %s\n' "$1" "$2"
  else
    printf '  MISMATCH  %-42s %s (header says %s)\n' "$1" "${2:-<not found>}" "$3"
    fail=1
  fi
}

header_value() { # header_value <field>  e.g. 'Requires PHP'
  grep -iE "^[[:space:]]*\*?[[:space:]]*$1:" "$main_file" | head -1 \
    | sed -E "s/.*$1:[[:space:]]*//" | tr -d '[:space:]' || true
}

php_min="$(header_value 'Requires PHP')"
wp_min="$(header_value 'Requires at least')"
[ -n "$php_min" ] || { echo "ERROR: no 'Requires PHP:' in $main_file" >&2; exit 1; }
[ -n "$wp_min" ] || { echo "ERROR: no 'Requires at least:' in $main_file" >&2; exit 1; }

if [ "${1:-}" = '--wp-min' ]; then
  echo "$wp_min"
  exit 0
fi

echo "Minimum PHP — $main_file header: $php_min"

readme_php="$(grep -iE '^Requires PHP:' readme.txt | head -1 \
  | sed -E 's/^[^:]*:[[:space:]]*//' | tr -d '[:space:]' || true)"
check 'readme.txt "Requires PHP"' "$readme_php" "$php_min"

composer_req_php="$(grep -oE '"php": *">=[0-9.]+"' composer.json \
  | grep -oE '[0-9]+\.[0-9]+' | head -1 || true)"
check 'composer.json require.php floor (>=)' "$composer_req_php" "$php_min"

composer_platform_php="$(grep -oE '"php": *"[0-9.]+"' composer.json \
  | grep -oE '[0-9]+\.[0-9]+' | head -1 || true)"
check 'composer.json config.platform.php' "$composer_platform_php" "$php_min"

phpcs_php="$(grep -oE 'name="testVersion" value="[0-9.]+-"' "$phpcs_file" \
  | grep -oE '[0-9]+\.[0-9]+' | head -1 || true)"
check "$phpcs_file testVersion" "$phpcs_php" "$php_min"

# phpstan encodes 8.2 as 80200 (MMmmpp).
phpstan_raw="$(grep -E '^[[:space:]]*phpVersion:' "$phpstan_file" \
  | grep -oE '[0-9]+' | head -1 || true)"
phpstan_php=''
[ -n "$phpstan_raw" ] && phpstan_php="$((phpstan_raw / 10000)).$(((phpstan_raw % 10000) / 100))"
check "$phpstan_file phpVersion" "$phpstan_php" "$php_min"

ci_php="$(grep -E '^[[:space:]]*php: \[' "$ci_file" \
  | grep -oE '[0-9]+\.[0-9]+' | sort -V | head -1 || true)"
check "$ci_file php matrix floor" "$ci_php" "$php_min"

echo "Minimum WordPress — $main_file header: $wp_min"

readme_wp="$(grep -iE '^Requires at least:' readme.txt | head -1 \
  | sed -E 's/^[^:]*:[[:space:]]*//' | tr -d '[:space:]' || true)"
check 'readme.txt "Requires at least"' "$readme_wp" "$wp_min"

phpcs_wp="$(grep -oE 'name="minimum_wp_version" value="[0-9.]+"' "$phpcs_file" \
  | grep -oE '[0-9]+\.[0-9]+' | head -1 || true)"
check "$phpcs_file minimum_wp_version" "$phpcs_wp" "$wp_min"

stubs_wp="$(grep -oE '"php-stubs/wordpress-stubs": *"\^[0-9.]+"' composer.json \
  | grep -oE '[0-9]+\.[0-9]+' | head -1 || true)"
check 'composer.json wordpress-stubs floor (^)' "$stubs_wp" "$wp_min"

if [ "$fail" -ne 0 ]; then
  echo "FAILED: minimum-version declarations disagree (see MISMATCH lines above)." >&2
  exit 1
fi

echo "OK: all minimum PHP/WP declarations agree with the plugin header."
