#!/usr/bin/env bash
#
# What the specs need from the site beyond a stock install: WooCommerce (this plugin
# requires it, so CI runs this before activating the plugin), the pt_BR core
# language pack, a must-use plugin that prints a foreign admin notice, and the fixture
# products the storefront specs visit. Idempotent:
# run it once against a local sandbox too.
#
# Usage:
#   tests/e2e/setup-site.sh <WordPress path>

set -euo pipefail

site="$1"
run() { wp --path="$site" --user=1 "$@"; }
# An ID from a command's output: some plugins print notices to stdout under WP-CLI.
id_from() { grep -E '^[0-9]+$' | head -1 || true; }

if ! run plugin is-active woocommerce; then
  run plugin install woocommerce --activate
fi
# Otherwise WooCommerce's first admin page view redirects to its setup wizard.
run transient delete _wc_activation_redirect >/dev/null 2>&1 || true

# The bundled-translations spec switches the user to pt_BR, which core only allows for an installed language.
run language core install pt_BR

# The notice spec needs a notice from code other than this plugin. The fixture prints only for the spec's cookie.
mu_dir="$(run eval 'echo WPMU_PLUGIN_DIR;' | tail -1)"
mkdir -p "$mu_dir"
cp "$(dirname "$0")/support/foreign-notice.php" "$mu_dir/installment-prices-for-woocommerce-e2e-notice.php"

# CONTRACT: tests/e2e/support/config.js names these slugs and prices.
product_id() { run post list --post_type=product --name="$1" --post_status=any --field=ID | id_from; }

if [ -z "$(product_id installment-prices-e2e-simple)" ]; then
  run wc product create --name='Installment Prices E2E Simple' --slug=installment-prices-e2e-simple \
    --regular_price=100 --status=publish --porcelain
fi

if [ -z "$(product_id installment-prices-e2e-out-of-stock)" ]; then
  run wc product create --name='Installment Prices E2E Out of Stock' --slug=installment-prices-e2e-out-of-stock \
    --regular_price=40 --manage_stock=true --stock_quantity=0 --status=publish --porcelain
fi

if [ -z "$(product_id installment-prices-e2e-variable)" ]; then
  id="$(run wc product create --name='Installment Prices E2E Variable' --slug=installment-prices-e2e-variable \
    --type=variable --status=publish --porcelain \
    --attributes='[{"name":"Size","options":["Small","Large"],"visible":true,"variation":true}]' | id_from)"
  run wc product_variation create "$id" --regular_price=50 --attributes='[{"name":"Size","option":"Small"}]' --porcelain
  run wc product_variation create "$id" --regular_price=80 --attributes='[{"name":"Size","option":"Large"}]' --porcelain
fi
