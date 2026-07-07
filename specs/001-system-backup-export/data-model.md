# Data Model: System Backup Export

## Entity: `Backup`

Represents a single backup archive that has been generated.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigInteger | PK, auto-increment | Unique identifier |
| `filename` | string(255) | NOT NULL | Name of the archive file on disk |
| `file_path` | string(500) | NOT NULL | Relative path from `storage/app/` |
| `file_size` | bigInteger | unsigned, nullable | File size in bytes |
| `status` | string(20) | NOT NULL, default 'pending' | One of: `pending`, `processing`, `completed`, `failed` |
| `notes` | text | nullable | Error messages or administrative notes |
| `started_at` | datetime | nullable | When backup generation started |
| `completed_at` | datetime | nullable | When backup finished (success or failure) |
| `created_at` | timestamp | NOT NULL | Record creation timestamp |
| `updated_at` | timestamp | NOT NULL | Record update timestamp |

### Indexes

- `idx_backups_status` on `status`
- `idx_backups_created_at` on `created_at` (for ordering)

### Validation Rules

- `filename`: required, string, max 255, unique (soft — filename collision unlikely with timestamps)
- `file_path`: required, string, max 500
- `status`: required, in: `pending`, `processing`, `completed`, `failed`

### State Transitions

```
pending → processing
processing → completed
processing → failed
```

Status never transitions backward. Failed backups can be manually deleted by the admin.

## Entity: `BackupArchive` (filesystem, not DB)

The actual physical file stored at `storage/app/backups/{filename}.zip`.

**Naming convention**: `moneyaccounts-backup-{YYYYMMDD}-{HHMMSS}-{random4}.zip`

**Contents of ZIP**:
```
moneyaccounts-backup-{timestamp}/
├── database.sql              # mysqldump output
└── storage/
    ├── public/
    │   ├── ...               # Uploaded files from storage/app/public/
    └── ...
```

## Relationships

- None — `Backup` is a standalone entity with no FK relationships to other tables in this version.

## Database Table

`backups` — corresponding migration creates this table with the columns defined above.
