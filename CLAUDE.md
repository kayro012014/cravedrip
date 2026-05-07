# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Running the Project

No build system or package manager. Open files directly in a browser:

- **Landing page:** open `index.html`
- **Admin panel:** open `admin/index.html`

For PHP/MySQL backend work (not yet implemented): place the folder in `/Applications/MAMP/htdocs/cravedrip/` and visit `http://localhost:8888/cravedrip/`.

## Architecture

### Two independent frontends

**Landing page** (`index.html` + `css/style.css` + `js/script.js`)
- Single-page marketing site with scroll animations, menu filter, and a placeholder contact form
- All interactivity is in `js/script.js`; no modules or bundling

**Admin panel** (`admin/`)
- Three pages: `index.html` (dashboard), `pos.html`, `inventory.html`
- Shared stylesheet: `admin/css/admin.css`
- All pages load `admin/js/data.js` first (global arrays), then their own JS file

### Data flow (frontend-only, no backend yet)

`admin/js/data.js` exports three global arrays: `MENU_ITEMS`, `INVENTORY_ITEMS`, `SAMPLE_ORDERS`. These are the single source of truth until PHP/MySQL is wired in.

- `pos.js` persists completed orders to `sessionStorage` under the key `posOrders`
- `dashboard.js` merges `SAMPLE_ORDERS` + `sessionStorage.posOrders` to calculate stats
- Inventory edits (add/edit/delete/adjust) are in-memory only — lost on page refresh

### CSS variables

Both stylesheets share the same color palette via `:root` variables. The canonical definitions live in `css/style.css`; `admin/css/admin.css` redefines them plus adds admin-specific tokens (`--sidebar-width`, `--header-height`, status colors, etc.). When adding new brand colors, update **both** files.

### Image paths

All images are stored locally in `assets/images/`. From admin pages, images are referenced as `../assets/images/<file>.jpg`.

## Planned Backend (PHP + MySQL via MAMP)

When connecting the backend, replace the global array declarations in `admin/js/data.js` with `fetch()` calls to PHP endpoints. The frontend JS already separates data loading from rendering, so each render function just needs its input array — the data source can be swapped without touching the render logic.
