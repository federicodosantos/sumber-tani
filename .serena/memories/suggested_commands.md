# Suggested Commands

## Dev workflow
- `composer dev` — runs server + queue listener + pail (logs) + vite concurrently
- `php artisan serve` — Laravel dev server
- `npm run dev` — Vite dev
- `npm run build` — Vite production build (also copies manifest.webmanifest into public/)
- `php artisan queue:listen --tries=1`
- `php artisan pail` — log tailing

## DB
- `php artisan migrate` / `migrate:fresh --seed`
- `php artisan tinker`

## Test / format
- `composer test` — clears config then runs `php artisan test` (PHPUnit)
- `vendor/bin/pint` — Laravel Pint (code formatter; configured in dev deps)
- `vendor/bin/phpunit`

## Setup
- `composer setup` — full bootstrap (install, env, key, migrate, npm install + build)

## Linux / WSL utilities
Standard GNU coreutils available: `ls`, `cd`, `grep`, `find`, `rg` (if installed), `git`, `cat`, `awk`, `sed`. Path uses Windows mount: `/mnt/g/Me/2. Works/sumber-tani`. Note the space in the path — quote when used in shell.

## Branches
- main = production
- staging = current working branch
