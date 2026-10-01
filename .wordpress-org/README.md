# WordPress.org plugin page assets

The images in `assets/` are **not** part of the distributed plugin ZIP. They are
served only on the plugin's public page at wordpress.org and live in the `assets/`
directory of the plugin's SVN repository — never in `trunk/` or a release `tag/`.

This `.wordpress-org/` directory version-controls them here so they exist before
being pushed to SVN. It is excluded from the built ZIP (see `.distignore`).
`deploy.yml` hands `assets/` to the
[10up `action-wordpress-plugin-deploy`](https://github.com/10up/action-wordpress-plugin-deploy)
action as its `ASSETS_DIR`, and the action mirrors it into SVN `assets/` verbatim:
keep only wp.org images there. Any other file, a README or a `.gitkeep`, is
published too.

## Expected files in `assets/` (filename convention is mandatory)

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
- Asset changes reach wp.org with the next release deploy, which mirrors `assets/`
  into SVN `assets/`: commit them here, since a file committed straight to SVN
  `assets/` and missing here is deleted by that mirror.
- Put the image files in `assets/` under exactly these names.
