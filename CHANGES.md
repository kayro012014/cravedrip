# CraveDrip — Backend & Security Changes

## Overview

Completed the full PHP/MySQL backend and hardened security across the entire admin system. All admin pages now pull live data from the database instead of hardcoded JavaScript arrays.

---

## New Files Created

### `api/helpers.php`
Shared utility library included by every API endpoint and auth file.

- `session_secure_start()` — sets `httponly`, `use_strict_mode`, `samesite=Strict` before calling `session_start()`
- `set_security_headers()` — outputs `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `X-XSS-Protection`
- `csrf_token()` — generates and stores a 64-char hex token in the session
- `csrf_field()` — returns a hidden `<input>` with the CSRF token for HTML forms
- `csrf_meta()` — returns a `<meta name="csrf-token">` tag for AJAX use
- `api_require_auth()` — checks session for `user_id`; returns `401 JSON` if missing (used by API endpoints, unlike `auth_check.php` which redirects)
- `api_require_csrf()` — checks `X-CSRF-Token` header or `_csrf` POST field against the session token using `hash_equals()`
- `api_json()` — sets `Content-Type: application/json` and exits
- `v_str()`, `v_float()`, `v_int()` — safe input readers from `$_POST` with trimming and length limits

---

### `api/menu.php`
REST endpoint for the café menu.

| Method | Action param | What it does |
|--------|-------------|--------------|
| GET | — | Returns all active menu items |
| POST | `add` | Inserts a new menu item |
| POST | `update` | Updates name, category, price, image |
| POST | `delete` | Soft-deletes via `is_active = 0` |

Validates: category must be one of `coffee`, `cold`, `tea`, `pastry`. Price must be > 0.

---

### `api/inventory.php`
REST endpoint for inventory management.

| Method | Action param | What it does |
|--------|-------------|--------------|
| GET | — | Returns all inventory items |
| POST | `add` | Inserts a new item |
| POST | `update` | Updates all fields |
| POST | `adjust` | Adds or deducts stock; rejects negative results |
| POST | `delete` | Hard-deletes the item |

Validates: category whitelist (`ingredients`, `supplies`, `baked`), unit whitelist (`kg`, `g`, `L`, `ml`, `pcs`, `pack`), stock and price cannot be negative.

---

### `api/orders.php`
REST endpoint for POS order creation and retrieval.

| Method | What it does |
|--------|-------------|
| GET `?date=YYYY-MM-DD` | Returns all orders for a given date (defaults to today) with item summary |
| POST (JSON body) | Creates an order + line items in a DB transaction |

Order numbers are generated as `ORD-YYYYMMDD-NNN` (e.g. `ORD-20260509-001`).  
Validates: payment method whitelist (`cash`, `gcash`, `card`), non-empty items array, total > 0.  
Uses `PDO::beginTransaction` / `rollBack` to keep orders + order_items in sync.

---

### `api/dashboard.php`
Read-only endpoint that powers the dashboard page.

Returns in a single request:
- Today's total revenue, order count, average order value
- Last 10 orders today (with item summary string)
- Full stock alert lists: `out` (stock ≤ 0) and `low` (stock ≤ reorder level)

---

## Modified Files

### `admin/pages/setup.php`
Added 5 new tables and seed data on top of the existing `users` table creation.

**New tables:**
- `login_attempts (id, ip, attempted_at)` — tracks failed login attempts per IP for rate limiting
- `menu_items (id, name, category, price, img, is_active, created_at, updated_at)`
- `inventory_items (id, name, category, unit, stock, reorder_level, cost_price, sell_price, created_at, updated_at)`
- `orders (id, order_number, subtotal, discount, vat_amount, total, payment_method, cash_received, change_amount, created_by, created_at)`
- `order_items (id, order_id, menu_item_id, item_name, item_price, quantity, line_total)` — FK to `orders` (cascade delete), FK to `menu_items` (set null on delete)

**Seeded on first run:**
- 14 menu items across all categories
- 20 inventory items (ingredients, supplies, baked goods)
- 1 default admin account (`admin / cravedrip2024`)

---

### `admin/pages/auth_check.php`
Replaced with a security-hardened version.

**Before:** Called `session_start()` with no configuration.  
**After:**
- Calls `session_secure_start()` for cookie hardening before any session is touched
- Periodic session ID regeneration every 30 minutes (`session_regenerate_id(true)`)
- Includes `helpers.php` and calls `set_security_headers()` + `csrf_token()` for all protected pages

---

### `admin/pages/login.php`
Major security upgrade.

**Added:**
- CSRF token generated on GET, verified on POST using `hash_equals()`
- Login rate limiting: queries `login_attempts` table; blocks after 5 failures in 15 minutes per IP
- Failed attempts are logged to `login_attempts`; successful login clears all attempts for that IP
- Fresh CSRF token generated on successful login (`bin2hex(random_bytes(32))`)
- Redirect-if-already-logged-in guard at the top
- Submit button disables itself with a spinner on click (prevents double-submit)
- Graceful fallback: if DB is unreachable (pre-setup), shows a clear error instead of crashing
- Security headers via `set_security_headers()`

---

### `admin/pages/logout.php`
Proper session destruction.

**Before:** Called `session_unset()` + `session_destroy()`.  
**After:** Clears `$_SESSION` array, explicitly expires the session cookie, then destroys the session — prevents session data from lingering.

---

### `admin/pages/profile.php`
Wired to the shared helpers.

**Before:** Used `require 'auth_check.php'` (which redirects instead of returning JSON).  
**After:**
- Uses `api_require_auth()` for a proper `401 JSON` response
- Validates CSRF token via `api_require_csrf()`
- Uses `set_security_headers()` and `v_str()` for input handling

---

### `admin/js/data.js`
Completely replaced — removed all mock data arrays.

**Before:** Exported `MENU_ITEMS`, `INVENTORY_ITEMS`, `SAMPLE_ORDERS` as hardcoded JS arrays.  
**After:** Exports shared API utilities used by all three admin pages:
- `getCsrf()` — reads the CSRF token from `<meta name="csrf-token">`
- `apiGet(path)` — fetch wrapper for GET requests
- `apiPost(path, data)` — fetch wrapper for POST; automatically includes `X-CSRF-Token` header; handles both `FormData` and JSON
- `showToast(type, msg)` — moved here from inline scripts in each page (was duplicated 3×)
- `initClock()` — moved here from each JS file (was duplicated 3×)

---

### `admin/js/dashboard.js`
Now fetches live data from `api/dashboard.php`.

**Before:** Read from `SAMPLE_ORDERS` + `sessionStorage.posOrders` + `INVENTORY_ITEMS`.  
**After:** Single `apiGet('dashboard.php')` call on load; renders revenue, order count, avg, stock alerts, recent orders table — all from the DB.

---

### `admin/js/pos.js`
Now loads menu from DB and saves orders to DB.

**Before:** Read menu from `MENU_ITEMS` global; saved orders to `sessionStorage`.  
**After:**
- `loadMenu()` — fetches `api/menu.php` on page load
- `confirmPayment()` — became `async`; POSTs order to `api/orders.php` with full cart, totals, and payment details; receipt displays the DB-assigned order number
- Removed `saveSession()` — sessionStorage is no longer used for orders
- Cart logic unchanged (still in-memory during the session)

---

### `admin/js/inventory.js`
All CRUD operations now go through the API.

**Before:** All edits were in-memory only — lost on page refresh.  
**After:**
- `loadItems()` — fetches `api/inventory.php` on page load
- `saveItem()` — POSTs `add` or `update` action, then reloads from DB
- `deleteItem()` — POSTs `delete` action, then reloads from DB
- `confirmAdj()` — POSTs `adjust` action (add/deduct), then reloads from DB
- All buttons disable during async operations to prevent double-submission

---

### `admin/index.php`, `admin/pages/pos.php`, `admin/pages/inventory.php`
Three identical changes across all three pages:

1. Added `<?= csrf_meta() ?>` in `<head>` so the CSRF token is available to `getCsrf()` in JS
2. Removed the duplicate `showToast()` function from each page's inline `<script>` (now lives in `data.js`)
3. Added `'X-CSRF-Token': getCsrf()` header to the profile modal's `fetch()` call (required now that `profile.php` validates CSRF)

---

## Security Summary

| Threat | Mitigation |
|--------|-----------|
| CSRF | Per-session token in `<meta>` tag; verified server-side on every mutating request |
| Brute-force login | Rate limit: 5 attempts / 15 min per IP via `login_attempts` table |
| Session fixation | `session_regenerate_id(true)` on login + every 30 min |
| Session hijacking | `httponly` + `samesite=Strict` cookie flags |
| SQL injection | 100% PDO prepared statements |
| XSS | All server-rendered output uses `htmlspecialchars()`; API responses are JSON |
| Clickjacking | `X-Frame-Options: SAMEORIGIN` |
| MIME sniffing | `X-Content-Type-Options: nosniff` |
| Unauthorized API access | `api_require_auth()` on every endpoint returns `401 JSON` (no redirect loop) |
| Invalid inputs | Server-side whitelists for categories, units, payment methods; numeric bounds checked |

---

## How to Use

### First-time setup
1. Start MAMP
2. Visit `http://localhost:8888/cravedrip/admin/pages/setup.php`
3. Confirm all steps show ✓
4. **Delete `admin/pages/setup.php` immediately**
5. Log in at `http://localhost:8888/cravedrip/admin/pages/login.php`
   - Username: `admin`
   - Password: `cravedrip2024`
6. Change your password via the profile modal (sidebar, bottom-left)
