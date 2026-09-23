# WordPress.org plugin page assets

These images are **not** part of the distributed plugin ZIP. They are served only
on the plugin's public page at wordpress.org and live in the `assets/` directory
of the plugin's SVN repository — never in `trunk/` or a release `tag/`.

This `.wordpress-org/` directory version-controls them here so they exist before
being pushed to SVN. It is excluded from the built ZIP (see `.distignore`). The
[10up `action-wordpress-plugin-deploy`](https://github.com/10up/action-wordpress-plugin-deploy)
and `action-wordpress-plugin-asset-update` actions read assets from exactly this
path, so the layout doubles as deploy input.

## Expected files (filename convention is mandatory)

WordPress.org maps these by **filename**, not by any manifest — names must match exactly.

### Banner — header image at the top of the plugin page
- `banner-772x250.png` (or `.jpg`) — required
- `banner-1544x500.png` (or `.jpg`) — optional hi-DPI / retina

### Icon — small logo in search results, the plugin card, and the updates screen
- `icon-128x128.png` (or `.jpg`) — required
- `icon-256x256.png` (or `.jpg`) — optional hi-DPI / retina
- `icon.svg` — optional; if present, takes precedence over the PNGs

### Screenshots — the "Screenshots" section
- `screenshot-1.png`, `screenshot-2.png`, … (`.jpg` also allowed)
- The number must match the caption order in `readme.txt`'s `== Screenshots ==`
  section: `screenshot-1.png` ↔ the 1st caption, `screenshot-2.png` ↔ the 2nd, etc.

## Notes
- PNG or JPG only for raster images. Keep file sizes reasonable.
- Asset changes go live on the .org page independently of a code release — you can
  update the banner/icon/screenshots without shipping a new plugin version.
- Drop the actual image files alongside this README; do not rename them.
