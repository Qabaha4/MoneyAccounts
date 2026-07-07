# Research: System Backup Export

## Backend Approach

### Decision: Use `mysqldump` via PHP's `proc_open` / Symfony Process component

**Rationale**: Most reliable way to get a consistent SQL dump. Laravel doesn't ship a built-in DB dumper. The `mysqldump` binary produces a complete, portable SQL file that can be restored with `mysql` CLI.

**Alternatives considered**:
1. Laravel's streaming query builder + custom dump — prone to inconsistencies, slow for large tables, no transaction handling
2. `spatie/laravel-db-dumper` package — well maintained, wraps `mysqldump`, but adds a dependency for what is essentially a one-liner
3. PHP `ZipArchive` for file archive + direct SQL dump via raw `mysqldump` command

**Chosen approach**: Service class (`BackupService`) that:
1. Creates a temp directory `storage/app/backups/tmp_*`
2. Runs `mysqldump` to dump the database into a SQL file inside that temp directory
3. Copies upload files into the temp directory
4. Creates a ZIP archive with `ZipArchive`
5. Moves the ZIP to `storage/app/backups/*.zip`
6. Cleans up the temp directory
7. Records the backup in the `backups` DB table

**Files stored**: Uploaded files live in `storage/app/public/` (symlinked to `public/storage`). Copy these into the dump.

### Decision: Stream downloads via Laravel's `response()->download()` or `Storage::download()`

**Rationale**: Laravel handles streaming natively — no extra work needed for large files.

## UI Approach

### Decision: Use Filament v4 Resource (`BackupResource`)

**Rationale**: Filament v4 is already installed and used for the admin panel (`/admin` path). Using a Filament resource aligns with existing patterns (UserResource, AuditLogResource, CurrencyResource, etc.) and avoids building a separate Vue page.

**Pattern** (based on existing codebase):
- Resource: `app/Filament/Admin/Resources/Backups/BackupResource.php`
- Table config: `app/Filament/Admin/Resources/Backups/Tables/BackupsTable.php`
- Pages: ListBackups (index page), with header action "Create Backup"
- Navigation group: "System Management" (consistent with existing resources)
- Route: auto-registered via `discoverResources` in `AdminPanelProvider`

**Backup creation flow**: Filament Action (modal or direct) that calls `BackupService::create()` synchronously or dispatches a queued job. Since backup can take time, dispatch a queued job and poll for completion, or run synchronously with a loading state in Filament's action feedback.

### Decision: Filament Actions for Download and Delete

**Download**: Custom Filament action on table rows that streams the file via `response()->download()`.
**Delete**: Standard Filament `DeleteAction` or custom action that removes file from disk + DB record.

## Storage & Cleanup

### Decision: `storage/app/backups/` with DB record

**Rationale**: Keeps backups inside the Laravel storage directory, outside web root. The `backups` database table tracks metadata.

**Cleanup strategy**: Manual deletion only (per spec). No auto-prune in v1.

## Key PHP Dependencies

- `ext-zip` — for `ZipArchive` class
- `ext-pdo` + `ext-pdo_mysql` — already required by Laravel
- MySQL `mysqldump` binary — must be available on the server
