# Implementation Plan: System Backup Export

**Branch**: `001-system-backup-export` | **Date**: 2026-07-07 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-system-backup-export/spec.md`

## Summary

Provide a backup management page inside the existing Filament v4 admin panel (`/admin`) to generate, download, and manage full system database + file backups as downloadable ZIP archives. Uses Filament Resource pattern consistent with existing UserResource, AuditLogResource, etc.

## Technical Context

**Language/Version**: PHP 8.2+ (Laravel 12), Filament v4.11

**Primary Dependencies**: Filament v4 (already installed), `zip` PHP extension, MySQL/MariaDB `mysqldump` binary, Laravel queues (for async backup generation)

**Storage**: Local filesystem under `storage/app/backups/` (Laravel filesystem disk config)

**Testing**: PHPUnit (Pest) for Unit + Feature tests

**Target Platform**: Linux web server (shared or VPS)

**Project Type**: Web application (Laravel + Filament admin + Vue SPA frontend)

**Performance Goals**: Backup generation for typical usage (<100MB DB + files) completes within 30 seconds; download streams without timeout

**Constraints**: Requires shell access for `mysqldump`; `ext-zip` enabled on server; sufficient disk space for at least 3 full backups

**Scale/Scope**: Single admin user; one database of <1GB expected for this application's lifetime

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

The constitution file at `.specify/memory/constitution.md` contains only placeholder/template content (no concrete principles filled in). No gates are triggered. This plan is not in violation of any project governance constraints.

## Project Structure

### Documentation (this feature)

```text
specs/001-system-backup-export/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output (streaming download response contract)
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
app/
├── Filament/
│   └── Admin/
│       └── Resources/
│           └── Backups/
│               ├── BackupsResource.php         # Resource class
│               ├── Tables/
│               │   └── BackupsTable.php         # Table configuration
│               └── Pages/
│                   └── ListBackups.php          # Index page (with header action)
├── Models/
│   └── Backup.php                              # Eloquent model
├── Services/
│   └── BackupService.php                       # Core backup logic
├── Jobs/
│   └── CreateBackupJob.php                     # Queued backup job
└── Console/
    └── Commands/
        └── CreateBackupCommand.php              # Optional CLI trigger

database/
├── migrations/
│   └── xxxx_xx_xx_create_backups_table.php

config/
└── filesystems.php                             # Add 'backups' disk config

tests/
├── Unit/
│   └── Services/
│       └── BackupServiceTest.php
└── Feature/
    └── Admin/
        └── BackupTest.php
```

**Structure Decision**: Filament Resource pattern matching the existing project conventions (UserResource, AuditLogResource, CurrencyResource). No custom Vue pages, no custom API routes — Filament auto-generates everything from the resource definition. A queued job handles async backup generation. The BackupService is a standalone service class testable in isolation.

## Complexity Tracking

> No constitution violations detected — complexity tracking not required.
