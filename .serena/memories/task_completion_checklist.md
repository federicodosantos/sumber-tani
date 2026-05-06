# Task Completion Checklist

When finishing a coding task in this Laravel project:

1. **Format**: run `vendor/bin/pint` on changed PHP files (or whole repo).
2. **Tests**: run `composer test` (= `php artisan config:clear && php artisan test`). If only touching specific area, `php artisan test --filter=...`.
3. **Migrations**: if a new migration added, verify `php artisan migrate` runs cleanly on a fresh DB. Never edit an already-committed migration — add a new one.
4. **Frontend changes**: run `npm run build` to confirm Vite build passes and the manifest.webmanifest copy step works. For UI work, test in browser via `composer dev` or `npm run dev`.
5. **Activity log / audit**: if touching models with logging, ensure `LogsActivity` traits and role assignment still work.
6. **Offline cashier**: if changing transaction schema/flow, verify the Dexie IndexedDB sync path (`offline_uuid`) still reconciles.
7. **PDF flows**: if invoices/receipts changed, render a sample via dompdf to verify.
8. **Don't commit unless asked**.
