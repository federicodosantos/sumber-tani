# Style & Conventions

## PHP / Laravel
- PSR-4 autoload: `App\` → `app/`, `Database\Factories\` → `database/factories/`, `Database\Seeders\` → `database/seeders/`.
- Formatter: **Laravel Pint** (PSR-12 by default). Run `vendor/bin/pint` before completing tasks.
- Standard Laravel conventions: PascalCase models/controllers, camelCase methods, snake_case DB columns/tables.
- Business logic placed in `app/Services/` (already two services exist) — prefer extracting from controllers as they grow.
- Migrations are timestamped per Laravel default; many additive migrations rather than editing existing ones (preserve prod history).

## Frontend
- Blade templates in `resources/views/`.
- Alpine.js for interactivity, with `@alpinejs/persist` for client-side state.
- Tailwind v4 via `@tailwindcss/vite` plugin.
- AutoNumeric for currency/number inputs.
- PWA: offline-first cashier flow uses Dexie/IndexedDB with `offline_uuid` reconciliation on transactions.

## Commit messages
Recent style observed (lowercased prefix + colon):
- `feat : ...` / `fix : ...` / `ref : ...` (note the space before colon in some, no space in others — inconsistent). Examples:
  - `ref : change decimal precision for total_price column from 10,2 to 13,2`
  - `feat: add unit price tracking and profit/loss reporting`
  - `fix : bug payment button in 1366x768 resolution`

## Decimals
Money columns use higher precision (e.g. total_price 13,2). PPN/discount precision was widened in 2026-04-19 migration.
