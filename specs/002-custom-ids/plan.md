# Build Plan: Custom ID Systems (UUID Users, Prefixed Account/Transaction IDs)

## Assumptions made

- `custom_id` becomes the actual PRIMARY KEY for accounts/transactions (same "swap the PK" intent as the user's `$table->uuid('id')->primary()` for users), not a secondary column. FKs that reference these tables are converted to string columns.
- Account ID: `acc-YYMMNNN` (monthly sequence, resets each month). Transaction ID: `trn-YYMMDDNNN` (daily sequence). NNN zero-padded, max 999 per period.
- Backfill order: `created_at` ASC, `id` ASC within each period; period derived from `created_at`.
- Upgrade migrations are guarded (no-op when `users.id`/`accounts.id`/`transactions.id` is already a string type) so fresh installs that run the modified originals don't get double-converted.
- Dev DB is SQLite; production may be MySQL/PgSQL — migration must work on all three.

## Approach

Update the original create migrations so fresh installs (`migrate:fresh`) get the new schema directly (honoring `$table->uuid('id')->primary()`), and add guarded upgrade migrations that convert existing data in-place: backfill new IDs → convert FK columns → swap primary keys → restore constraints. Users use Laravel's built-in `HasUuids`; accounts/transactions use a new shared `HasCustomId` trait (the "manual implementation" pattern: `getIncrementing()=false`, `getKeyType()='string'`, ID generated in `creating`). Sequence generation uses a new `custom_id_counters` table with `lockForUpdate` for atomicity.

## Steps

1. `database/migrations/0001_01_01_000000_create_users_table.php` — change `$table->id()` to `$table->uuid('id')->primary();`; change `sessions.user_id` to `$table->string('user_id', 36)->nullable()->index();`. Verify: `php artisan migrate:fresh` succeeds.

2. `database/migrations/2025_09_29_204608_create_accounts_table.php` — `$table->string('id', 20)->primary();` and `$table->foreignUuid('user_id')->constrained()->onDelete('cascade');` (currency_id stays `foreignId`). Verify: fresh migrate.

3. `database/migrations/2025_09_29_204615_create_transactions_table.php` — `$table->string('id', 20)->primary();`, `foreignUuid('user_id')`, `$table->string('account_id', 20)` + `->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade')`, same for `transfer_to_account_id` (nullable, `onDelete('set null')`). Verify: fresh migrate.

4. `database/migrations/2026_07_30_020000_create_activities_table.php` — `foreignUuid('user_id')`, `subject_id` → `$table->string('subject_id', 36)->nullable();`. Verify: fresh migrate.

5. `database/migrations/2025_10_08_012347_create_audit_logs_table.php` — `foreignUuid('user_id')`, `model_id` → `$table->string('model_id', 36);`. Verify: fresh migrate.

6. New: `database/migrations/YYYY_MM_DD_HHMMSS_create_custom_id_counters_table.php` — columns `id` (bigIncrements), `model_type` (string), `period` (string), `last_number` (integer, default 0); unique composite index `[model_type, period]`. Verify: `php artisan migrate`.

7. New: `database/migrations/YYYY_MM_DD_HHMMSS_convert_users_to_uuid.php` — **Guard**: `if (in_array(Schema::getColumnType('users','id'), ['integer','bigint']))` proceed, else return. Then:
   (a) drop FKs `accounts_user_id_foreign`, `transactions_user_id_foreign`, `activities_user_id_foreign`, `audit_logs_user_id_foreign` (look up FK names via `information_schema`/`sqlite_master` first — do not hardcode blindly);
   (b) add `uuid` char(36) nullable, backfill chunked with `Str::uuid()`;
   (c) convert `accounts.user_id`, `transactions.user_id`, `activities.user_id`, `audit_logs.user_id` to char(36) and update values via subquery join on old `users.id`;
   (d) convert `sessions.user_id` to string(36) + update values (note: invalidates existing sessions — accepted);
   (e) PK swap on users: MySQL first `ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL` to strip auto_increment, then `dropPrimary`, rename `id→legacy_id`, rename `uuid→id`, `primary('id')`, drop `legacy_id` (use Blueprint ops so SQLite rebuilds transparently);
   (f) re-add the four FKs as `foreignUuid('user_id')->constrained(...)` with original onDelete rules.
   Verify: `php artisan migrate` on the existing sqlite DB — all users have uuid `id`, related tables' `user_id` values match.

8. New: `database/migrations/YYYY_MM_DD_HHMMSS_convert_accounts_to_custom_id.php` — **Guard** on `accounts.id` being integer. Then:
   (a) add `custom_id` char(20) nullable;
   (b) backfill per month group (`created_at` order): `acc-` + `ym` + zero-padded index+1;
   (c) seed `custom_id_counters` with `(account, period, last_number=max)` per group via upsert;
   (d) drop transactions FKs `transactions_account_id_foreign` + `transactions_transfer_to_account_id_foreign`;
   (e) convert both columns to char(20) + update values via join on old `accounts.id`;
   (f) polymorphic update: `activities.subject_id` and `audit_logs.model_id` → new custom_id where `subject_type`/`model_type` = `App\Models\Account`;
   (g) PK swap (same recipe as step 7e);
   (h) re-add transactions FKs referencing `accounts(id)`.
   Verify: `php artisan migrate` — account ids are `acc-YYYYMMNNN`, transactions point to them.

9. New: `database/migrations/YYYY_MM_DD_HHMMSS_convert_transactions_to_custom_id.php` — **Guard** on `transactions.id` integer. Backfill daily groups (`trn-` + `ymd` + NNN), seed counters for `(transaction, period)`, polymorphic update for `App\Models\Transaction` in activities/audit_logs, PK swap. No FK re-adds needed (nothing references transactions). Verify: `php artisan migrate` — `trn-260730001` style ids.

10. `app/Models/User.php` — add `use Illuminate\Database\Eloquent\Concerns\HasUuids;` (remove nothing; existing `boot()` AuditLog hooks stay). Verify: `User::factory()->create()->id` is a UUID string.

11. New: `app/Models/Concerns/HasCustomId.php` — `getIncrementing(): false`, `getKeyType(): 'string'`, `bootHasCustomId()` registers `static::creating` that, if the key attribute is empty, computes period (`protected static function customIdPeriodFormat()`), locks `custom_id_counters` row (`lockForUpdate` + `upsert` with `last_number + 1`), and assigns `prefix . period . str_pad(n, 3, '0', STR_PAD_LEFT)`.

12. `app/Models/Account.php` — `use HasCustomId;` with config: prefix `'acc-'`, period format `'ym'`, counter model_type `'account'`. Verify: `Account::factory()->create()->id` matches `acc-\d{8}`.

13. `app/Models/Transaction.php` — `use HasCustomId;` with prefix `'trn-'`, format `'ymd'`, model_type `'transaction'`. Verify: `Transaction::factory()->create()->id` matches `trn-\d{10}`.

14. `app/Repositories/AccountRepository.php` + `app/Services/AccountService.php` — `findById(int $id)` → `findByCustomId(string $customId)` (query `where('id', $customId)`); `getAccountById(int $accountId)` → `getAccountByCustomId(string $customId)`. Update call sites in `app/Http/Controllers/AccountManagementController.php` (unrouted — must still compile). Verify: `php artisan test --filter=AccountManagementTest`.

15. `tests/Feature/AccountManagementTest.php` (+ any test using numeric ids/`findById`) — update to use factory-created models' `id` strings or `findByCustomId`. Verify: `php artisan test` — full suite green.

16. Final verification: back up the sqlite DB file, run `php artisan migrate` on it and confirm row counts match before/after and IDs follow the new formats; `php artisan migrate:fresh --seed`; run `php artisan test`; spot-check dashboard, accounts list, and Filament admin panel render.

## Do not

- Do NOT change `currencies` or `backups` tables (keep bigint PKs) — only `users`, `accounts`, `transactions`, `activities`, `audit_logs`, `sessions` are in scope.
- Do NOT modify `routes/web.php` — route model binding uses the PK, so `/accounts/{account}` now binds the string custom id automatically.
- Do NOT add Composer/npm dependencies (`HasUuids` is built-in).
- Do NOT alter `Activity`/`AuditLog` model logic — only their table columns.
- Do NOT run `migrate:fresh` on a real database before backing it up.

## Risk notes

- Primary-key type surgery is the riskiest part: MySQL requires stripping auto_increment before `DROP PRIMARY KEY`, and FK constraints must be dropped before the PK swap (defensive FK-name discovery required). Test on a DB copy first.
- Backfill must run **before** any column rename or PK drop — order within each migration is critical.
- Polymorphic `subject_id`/`model_id` conversions must match `subject_type`/`model_type` values exactly (`App\Models\Account`, `App\Models\Transaction`).
- All existing sessions are invalidated by the users conversion (users must re-login) — expected.
- URLs change from `/accounts/1` to `/accounts/acc-2607001`; Laravel's `route()` helper adapts, but hardcoded paths in `resources/js/` Vue components must be audited.
- Backup/Restore round-trip with string ids must still pass — run `php artisan test --filter=BackupTest` and `--filter=RestoreTest` explicitly.
