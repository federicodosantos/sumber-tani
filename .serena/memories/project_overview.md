# Sumber Tani — Project Overview

POS / inventory / finance management web app for an agricultural / retail business ("Sumber Tani"). Built on Laravel 12.

## Domain modules (inferred from controllers/models)
- **Cashier / Transactions**: `CashierController`, `TransactionController`, `TransactionDetailController`. Models: `Transaction`, `TransactionDetail`. Offline-capable (IndexedDB via Dexie, offline_uuid column).
- **Products & Inventory**: `ProductController`, `ProductStockController`, `ItemCategoryController`. Models: `Product`, `ProductStock`, `ItemCategory`. Tracks unit_price, expired_date, stock movements.
- **Purchases (procurement)**: `ProductPurchaseController`. Models: `ProductPurchase`, `ProductPurchaseDetail`. Supports PPN (VAT), discounts.
- **Customers / Invoices / Debt**: `CustomerR2Controller`. Models: `Customer`, `Invoice`, `DebtPayment`, `DebtPaymentDetail`, `CustomerProductPrice` (per-customer pricing).
- **Reports**: `DashboardController`, `FinanceReportController` (profit/loss using buying_price tracking on transaction_details).
- **Activity Log**: spatie/laravel-activitylog with role tracking.
- **PDF**: barryvdh/laravel-dompdf (likely invoices/receipts).
- **QZ Tray**: `QzSecurityController` for secure thermal printer signing.

## Tech stack
- PHP 8.2+, Laravel 12
- Frontend: Blade + Alpine.js (with persist plugin), Tailwind v4, Vite 7
- PWA: vite-plugin-pwa, workbox-window, manifest.webmanifest
- Offline DB: Dexie (IndexedDB)
- Numeric input: AutoNumeric
- Auth scaffolding: Laravel Breeze
- DB: see .env (likely MySQL given doctrine/dbal + decimal precision migrations)
- PDF: barryvdh/laravel-dompdf
- Activity log: spatie/laravel-activitylog
- Test: PHPUnit 11

## Structure highlights
- `app/Services/` — `DashboardService`, `ProductStockService` (business logic extracted from controllers)
- `app/View/` — view composers/components
- `routes/web.php`, `routes/auth.php` (Breeze), `routes/console.php`
- `resources/views/` Blade templates, `resources/js/`, `resources/css/`
- `database/migrations/` — extensive history; recent work: customer pricing, invoices/debt refactor, profit tracking
- `backfill_prices.php` — root-level one-off script
