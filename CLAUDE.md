# Catatan

Mobile-first PWA for logging routine activities ("kapan terakhir ganti sprei?"). Primary persona: ibu rumah tangga. UI text is Indonesian; code, comments and commit messages are English.

## Stack

- Laravel 12 API + Vue 3 SPA (Vite, vue-router, Pinia, Tailwind 4) in one repo, served from one domain.
- Auth: Google via Socialite, session cookies via Sanctum `statefulApi()`. No passwords.
- `routes/web.php` ends with a catch-all that serves `resources/views/app.blade.php`; add web routes above it and keep `api/`, `auth/` excluded.
- Users must accept the current `config('catatan.terms_version')` before using the app; the Vue router redirects to `/persetujuan` until they do.
- Data model: every user gets a personal `Household` (`User::currentHousehold()`); `Activity` belongs to a household, `Entry` to an activity. Always scope queries through the user's household.
- Entries carry a client-generated UUID so retries (and later offline sync) are idempotent. Times are stored in UTC; calendar stats take the viewer's `tz` query param.
- API routes that touch household data sit behind the `terms` middleware.
- SQLite for local dev and tests, MySQL in production.

## Commands

- `composer run dev` runs server, queue, logs and Vite.
- `php artisan test` (PHPUnit feature tests in `tests/Feature`).
- `vendor/bin/pint` before committing; CI runs `pint --test`, `npm run build` and the tests.

## Product guardrails

- Logging must take one tap; anything extra is optional and comes after the save.
- Keep server costs low (target VPS ≤ Rp100rb/month): no Redis, no paid APIs.
