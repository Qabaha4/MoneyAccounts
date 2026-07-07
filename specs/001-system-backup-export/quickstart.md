# Quickstart: System Backup Export

## Prerequisites

- PHP 8.2+ with `ext-zip` and `ext-pdo_mysql`
- `mysqldump` binary installed and on `$PATH`
- Laravel storage directory writable (`storage/app/`)
- Queue worker running (if using queued backup jobs)
- App configured, database migrated

## Setup

```bash
# Run migration
php artisan migrate

# Verify the Filament resource auto-registered
php artisan route:list --path=admin/backups
# Expected: shows backups resource routes

# Verify artisan command works
php artisan backup:create --dry-run
```

## Validation Scenarios

### Scenario 1: Create backup via Filament admin UI

1. Log in to `/admin`
2. Navigate to "Backups" in the sidebar (under "System Management")
3. Click "Create Backup" header action
4. Wait for the action to complete (or see success notification)
5. A new row appears in the table with status "completed"

### Scenario 2: Create backup via CLI

```bash
php artisan backup:create
```

**Expected output**: Backup created successfully.

```bash
# Verify backup in database
php artisan tinker --execute="echo App\Models\Backup::count();"
```

Expected: `1`

```bash
# Verify file on disk
ls -la storage/app/backups/
```

Expected: `moneyaccounts-backup-*.zip` file present.

### Scenario 3: Download backup via Filament

1. In the Backups table, click the "Download" action on any completed backup
2. Browser downloads the ZIP file
3. Verify with `unzip -l <filename.zip>` — should contain `database.sql` and `storage/` directory

### Scenario 4: Delete backup via Filament

1. In the Backups table, click the "Delete" action on a backup
2. Confirm the deletion in the modal
3. Row disappears, file removed from disk

### Scenario 5: PHPUnit tests

```bash
php artisan test --filter=BackupTest
```

Expected: All tests pass.

### Scenario 6: Uploaded files assertion

After backup creation:
```bash
# Verify backup ZIP contains the uploaded files
python3 -c "
import zipfile
z = zipfile.ZipFile('storage/app/backups/moneyaccounts-backup-*.zip')
print([f.filename for f in z.filelist])
"
```

Expected: lists `database.sql` and files under `storage/public/`.
