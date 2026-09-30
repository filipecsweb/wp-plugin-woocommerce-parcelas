#!/usr/bin/env bash
#
# The plugin's translations. languages/<text-domain>-<locale>.po are the only
# translation files in git: they hold the translated text. Everything else is
# generated, and languages/.gitignore keeps it out of git: the .pot (the strings
# extracted from the code, shipped as the template for translators working outside
# translate.wordpress.org), the .mo (read by PHP) and the .json (read by the JS).
#
#   bin/i18n.sh add <locale>   start a new locale (a WordPress locale, e.g. de_DE)
#   bin/i18n.sh sync           update every .po from the code, then compile
#   bin/i18n.sh build          write the .pot and compile every .po into .mo + .json
#                              (npm run build runs this)
#   bin/i18n.sh check          fail on an untranslated or fuzzy string, a translation that
#                              breaks a placeholder, or a make-pot audit warning (edits no .po)
#
# Requirements on PATH: wp (WP-CLI); add, sync and check also need npm and gettext.

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

main_file="woocommerce-parcelas.php"
header() { # <name>: that header's value in the main plugin file
  grep -iE "^[[:space:]]*\*?[[:space:]]*$1:" "$main_file" | head -1 \
    | sed -E "s/.*$1:[[:space:]]*//" | tr -d '[:space:]' || true
}
domain="$(header 'Text Domain')"
[ -n "$domain" ] || { echo "ERROR: no 'Text Domain:' header in $main_file" >&2; exit 1; }
dir="languages"
# The runtime loads translations from Domain Path; languages/.gitignore and CONTRIBUTING assume this folder.
[ "$(header 'Domain Path')" = "/$dir" ] || { echo "ERROR: $main_file's 'Domain Path:' must be /$dir, where this script writes" >&2; exit 1; }

shopt -s nullglob
# GOTCHA: loop over ${pos[@]+"${pos[@]}"}: bash 3.2 (macOS's) aborts on an empty "${pos[@]}" under set -u.
pos=("$dir/$domain"-*.po)

tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
pot="$tmp/$domain.pot"

need() {
  local cmd
  for cmd in "$@"; do
    command -v "$cmd" >/dev/null 2>&1 || { echo "ERROR: required command not found on PATH: $cmd" >&2; exit 1; }
  done
}

# GOTCHA: make-pot can't parse TSX, so it reads the BUILT scripts: build first.
# WHY --slug: make-pot would take it from the checkout folder (the repo name in CI) for the header's support URL.
make_pot() { # <out.pot> [make-pot flags...]
  wp i18n make-pot . "$1" --domain="$domain" --slug="$domain" --exclude=dist "${@:2}" >/dev/null
}

# Writes $pot, and the make-pot audit's warnings to $tmp/audit.log.
extract() {
  npm run build
  if ! make_pot "$tmp/raw.pot" 2>"$tmp/stderr.log"; then
    cat "$tmp/stderr.log" >&2
    exit 1
  fi
  grep '^Warning:' "$tmp/stderr.log" >"$tmp/audit.log" || true
  # WHY php-format: msgcat drops WP-CLI's js-format flag (gettext's own javascript-format rejects
  # %1$s), and @wordpress/i18n's sprintf takes PHP's placeholders, so msgfmt --check can compare them.
  # WHY file-only references: line numbers move with every edit, and make-json needs only the file.
  sed -E 's/^(#,.*)js-format/\1php-format/' "$tmp/raw.pot" | msgcat --add-location=file - -o "$pot"
}

cmd_build() {
  need wp
  # Everything but the .po is generated: clearing it drops a removed locale's or a renamed script's leftovers.
  rm -f "$dir"/*.pot "$dir"/*.mo "$dir"/*.json
  # The audit gates in check, so the build stays quiet.
  make_pot "$dir/$domain.pot" --skip-audit
  [ "${#pos[@]}" -gt 0 ] || return 0
  wp i18n make-mo "$dir" >/dev/null
  # --no-purge: without it make-json deletes the JS strings from the .po.
  wp i18n make-json "$dir" --no-purge >/dev/null
}

cmd_sync() {
  need npm wp msgcat msgfmt
  extract
  cat "$tmp/audit.log" >&2
  local po
  for po in ${pos[@]+"${pos[@]}"}; do
    wp i18n update-po "$pot" "$po" >/dev/null
  done
  cmd_build
  [ "${#pos[@]}" -gt 0 ] || { echo "No locales yet: bin/i18n.sh add <locale>" >&2; return 0; }
  for po in ${pos[@]+"${pos[@]}"}; do
    printf '%s: ' "$po" >&2
    msgfmt --statistics -o /dev/null "$po"
  done
}

cmd_check() {
  need npm wp msgcat msgcmp msgfmt
  extract
  local failed=0 po
  if [ -s "$tmp/audit.log" ]; then
    echo "make-pot audit:" >&2
    cat "$tmp/audit.log" >&2
    failed=1
  fi
  for po in ${pos[@]+"${pos[@]}"}; do
    echo "==> $po" >&2
    # Any skipped sync, stale references included: make-json maps each string to its script by them.
    cp "$po" "$tmp/synced.po"
    wp i18n update-po "$pot" "$tmp/synced.po" >/dev/null
    cmp -s "$po" "$tmp/synced.po" || { echo "$po is behind the code: run bin/i18n.sh sync" >&2; failed=1; }
    # Untranslated and fuzzy strings.
    msgcmp "$po" "$pot" || failed=1
    msgfmt --check -o /dev/null "$po" || failed=1
  done
  if [ "$failed" -ne 0 ]; then
    echo "Not ready: fix each audit warning in the code; for the rest, run bin/i18n.sh sync and translate (CONTRIBUTING.md → Translations)." >&2
    exit 1
  fi
  echo "Translations complete: ${#pos[@]} locale(s)." >&2
}

cmd_add() {
  local locale="${1:-}"
  [[ "$locale" =~ ^[a-z]{2,3}(_[A-Za-z0-9]+)*$ ]] || { echo "Usage: bin/i18n.sh add <locale>   (e.g. de_DE)" >&2; exit 2; }
  local po="$dir/$domain-$locale.po"
  [ ! -e "$po" ] || { echo "ERROR: $po already exists" >&2; exit 1; }
  need npm wp msgcat msginit
  extract
  msginit --no-translator --locale="$locale" --input="$pot" --output-file="$po"
  # GOTCHA: msginit shortens the Language header (de_DE -> de); WordPress names the full locale.
  sed "s/^\"Language: .*/\"Language: $locale\\\\n\"/" "$po" >"$tmp/new.po"
  mv "$tmp/new.po" "$po"
  # WP-CLI's own formatting, so the next sync doesn't rewrap the whole file.
  wp i18n update-po "$pot" "$po" >/dev/null
  pos=("$dir/$domain"-*.po)
  cmd_build
  echo "Created $po: translate it (CONTRIBUTING.md → Translations), then run bin/i18n.sh check." >&2
}

case "${1:-}" in
  add)   shift; cmd_add "${1:-}" ;;
  sync)  cmd_sync ;;
  build) cmd_build ;;
  check) cmd_check ;;
  *)     awk 'NR > 1 && /^#/ { sub(/^# ?/, ""); print; next } NR > 1 { exit }' "$0" >&2; exit 2 ;;
esac
