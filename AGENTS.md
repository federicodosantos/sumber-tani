# AGENTS.md

Laravel 12 POS / inventory / finance web app ("Sumber Tani") for an Indonesian agricultural retail business. Blade + Alpine.js (persist) + Tailwind v4 + Vite 7, PWA, offline cashier via Dexie/IndexedDB, Breeze auth. UI text and route URLs are largely Indonesian. **main = production; work on a feature branch, not main.**

## Commands

- `composer dev` — runs server + `queue:listen --tries=1` + `pail` (logs) + Vite concurrently. Use for full-stack dev.
- `composer test` — `php artisan config:clear` then `php artisan test`. Single file: `php artisan test --filter=...`.
- `vendor/bin/pint` — code formatter (PSR-12). Run on changed files before finishing.
- `npm run build` — Vite build; then copies `public/build/manifest.webmanifest` to `public/manifest.webmanifest`. Regenerates `public/sw.js`. `public/build/` is gitignored, but `public/sw.js` and `public/manifest.webmanifest` are tracked — commit them after a build.
- `composer setup` — full bootstrap (install, .env, key, migrate, npm install + build).

## Database

- Dev DB is MySQL (`DB_DATABASE=sumber_tani` per `.env.example`); tests run on SQLite `:memory:` via phpunit.xml — MySQL-only SQL breaks tests.
- **Never edit a committed migration.** Add a new additive migration. Some past migrations are pure data-migrations (e.g. `migrate_existing_data_for_invoices`) with hand-written backfill loops.
- `backfill_prices.php` at repo root is a manual one-off script; it bootstraps Laravel directly and runs model updates (unit_price / buying_price backfill).

## Architecture

- Business logic lives in `app/Services/` (`DashboardService`, `ProductStockService`, `TransactionReversalService`); controllers stay thin. Follow this pattern for new logic.
- Money columns use high decimal precision (13,2). Currency inputs are AutoNumeric via the `input-rupiah` Blade component, which defaults to 2 decimal places — pass `decimals="3"` on fields bound to `decimal(…,3)` columns (purchase & stock), or editing silently truncates stored 3-decimal values.
- Offline cashier: `resources/js/db.js` (Dexie) + `resources/js/cashier.js` reconcile transactions via `offline_uuid`. If you change the transaction schema/flow, verify this sync path still works.
- Activity logging via spatie/laravel-activitylog with a custom `role` column. Don't break it when touching models.
- PDFs (invoices/receipts) use barryvdh/laravel-dompdf.
- QZ Tray thermal printing: `QzSecurityController` signs requests with `storage/app/private/qz/private-key.pem` (cert at `storage/app/private/qz/digital-certificate.txt`). QZ routes are intentionally public (no auth).
- PWA (vite.config.js): `navigateFallback: null` because Laravel serves HTML, and checkout/localhost/ws requests are `NetworkOnly`. Don't change casually.

## Gotchas

- Some routes have no names (e.g. `/receipt/{id}`, `/checkout`); `named routes` coexist with anonymous ones in `routes/web.php`.
- Tests are sparse — only Breeze examples. Don't assume a test suite covers domain behavior.
- Commit style is `feat:` / `fix:` / `ref:` prefixes (spacing inconsistent in history — don't "fix" it).