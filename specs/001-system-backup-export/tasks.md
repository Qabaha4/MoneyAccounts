# Tasks: System Backup Export

**Input**: Design documents from `specs/001-system-backup-export/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md
**Tests**: Test tasks included (TDD approach)

## Format: `- [ ] [ID] [P?] [Story] Description with file path`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Include exact file paths

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [X] T001 Create `backups` filesystem disk in `config/filesystems.php` pointing to `storage/app/backups/`
- [X] T002 Add `backups/` directory to `storage/app/.gitignore`
- [X] T003 Create `Backup` Eloquent model in `app/Models/Backup.php` with fillable, casts, and status constants
- [X] T004 Create migration `create_backups_table` in `database/migrations/` with columns per `data-model.md`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core service + job that MUST be complete before Filament resource can be built

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T005 Create `BackupService` in `app/Services/BackupService.php` with `create()`, `delete()`, `listAll()`, `findById()`, `getDownloadPath()` methods
- [X] T006 Implement backup generation logic in `BackupService::create()`: create temp dir, run `sqlite3 .dump`, copy files from `storage/app/public/`, build ZIP via `ZipArchive`, move to `storage/app/backups/`
- [X] T007 Create `CreateBackupJob` in `app/Jobs/CreateBackupJob.php` dispatching to queue with `BackupService::create()`
- [X] T008 [P] Create `CreateBackupCommand` in `app/Console/Commands/CreateBackupCommand.php` with `php artisan backup:create` (sync execution for CLI trigger)

**Checkpoint**: Foundation ready — backup can be created via CLI and stored on disk.

---

## Phase 3: User Story 1 — Create & Download Backup (Priority: P1) 🎯 MVP

**Goal**: Admin can view a backup list page in Filament, create a new backup via button click, and download completed backups.

**Independent Test**: Navigate to `/admin/backups`, click "Create Backup", wait for success, download the ZIP, verify file opens correctly.

### Tests for User Story 1

> **NOTE**: Write these tests FIRST, ensure they FAIL before implementation

- [X] T009 [P] [US1] Unit test `BackupService::create()` in `tests/Feature/BackupTest.php` — verify ZIP file created on disk with `database.sql` and `storage/` contents
- [X] T010 [P] [US1] Feature test backup creation in `tests/Feature/BackupTest.php` — verify backup record and status

### Implementation for User Story 1

- [X] T011 [P] [US1] Create `BackupsResource` in `app/Filament/Admin/Resources/Backups/BackupsResource.php` with `navigationGroup: 'System Management'`, `navigationIcon: 'heroicon-o-archive-box'`, `navigationSort: 4`
- [X] T012 [P] [US1] Create `BackupsTable` in `app/Filament/Admin/Resources/Backups/Tables/BackupsTable.php` with columns: filename, file_size (formatted), status (badge), created_at (dateTime + since)
- [X] T013 [P] [US1] Create `ListBackups` page in `app/Filament/Admin/Resources/Backups/Pages/ListBackups.php` with header action "Create Backup" that dispatches `CreateBackupJob`
- [X] T014 [US1] Implement header action "Create Backup" in `ListBackups.php` — dispatches job, shows success notification via Filament notifications
- [X] T015 [US1] Add `DownloadAction` as a record action in `BackupsTable.php` that streams the ZIP file via route download
- [X] T016 [US1] Add status badge column to `BackupsTable.php` with color mapping: `completed` → success, `processing` → warning, `failed` → danger, `pending` → gray

**Checkpoint**: At this point, User Story 1 should be fully functional — admin can create, view, and download backups.

---

## Phase 4: User Story 2 — Manage Backup History (Priority: P2)

**Goal**: Admin can view full backup history with metadata, delete old backups.

**Independent Test**: Create two backups, verify both appear in the list with correct metadata. Delete one, verify it's removed from list and disk.

### Tests for User Story 2

- [X] T017 [P] [US2] Unit test `BackupService::delete()` in `BackupTest.php` — verify file removed from disk and DB record deleted
- [X] T018 [P] [US2] Feature test backup deletion in `BackupTest.php` — verify record gone

### Implementation for User Story 2

- [X] T019 [P] [US2] Implement `BackupService::listAll()` returning paginated results
- [X] T020 [US2] Implement `BackupService::delete()` in `BackupService.php` — removes ZIP from disk + deletes DB record
- [X] T021 [US2] Add `DeleteAction` as record action in `BackupsTable.php` with confirmation modal
- [X] T022 [US2] Ensure list page uses `->defaultSort('created_at', 'desc')` and `->paginated([10, 25, 50])`
- [X] T023 [US2] Add empty state to `BackupsTable.php` with "No backups yet" message and "Create your first backup" call-to-action

**Checkpoint**: User Stories 1 AND 2 should both work independently.

---

## Phase 5: User Story 3 — Error Handling & Storage Management (Priority: P2)

**Goal**: System gracefully handles errors during backup creation, warns about storage constraints.

**Independent Test**: Fill disk (simulate), attempt backup, verify clear error message. Download a corrupted backup, verify proper 404/error.

### Tests for User Story 3

- [ ] T024 [P] [US3] Unit test `BackupService::create()` failure handling — verify DB status set to `failed` with notes
- [ ] T025 [P] [US3] Unit test disk space check — verify service prevents backup when disk space < 500MB

### Implementation for User Story 3

- [X] T026 [US3] Add disk space check in `BackupService::create()` — check `disk_free_space()`, throw exception if < 500MB with clear message "Insufficient disk space"
- [X] T027 [US3] Wrap backup generation in try/catch in `CreateBackupJob` — on failure, set `status = 'failed'`, store error message in `notes`, fire Filament notification
- [X] T028 [US3] Add `->tooltip()` on file_size column showing exact bytes
- [X] T029 [US3] Prevent delete on backups with `status = 'processing'` — block action with explanation notification

**Checkpoint**: All user stories should now be independently functional.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final improvements across all stories

- [X] T030 [P] Add CLI summary to `CreateBackupCommand.php` — output backup ID, filename, and elapsed time on completion
- [ ] T031 Run `php artisan migrate:fresh --seed` and verify full backup cycle (create → list → download → delete)
- [X] T032 [P] Add Filament navigation badge showing count of failed backups (if any) on the Backups nav item
- [ ] T033 Run `quickstart.md` validation against all scenarios

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — can start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 — BLOCKS all user stories
- **Phase 3 (US1 — P1)**: Depends on Phase 2 — 🎯 MVP scope
- **Phase 4 (US2 — P2)**: Depends on Phase 2; integrates with US1 components
- **Phase 5 (US3 — P2)**: Depends on Phase 2; extends US1 error handling
- **Phase 6 (Polish)**: Depends on all desired stories complete

### User Story Dependencies

- **User Story 1 (P1)**: MVP — can be tested independently after Phase 2
- **User Story 2 (P2)**: Independent of US1 except sharing the `BackupService`
- **User Story 3 (P2)**: Extends error paths in `BackupService` created in US1

### Within Each User Story

- Tests written and FAIL before implementation
- Model → Service → Filament resource/page

### Parallel Opportunities

- T001/T002 can run in parallel (filesystem config + gitignore)
- T003/T004 can run in parallel (model + migration)
- T008 is parallel with T005–T007 (CLI command vs service/job)
- T009/T010 (unit + feature tests) can run in parallel
- T011/T012/T013 (resource + table + page) can run in parallel
- T019/T020 (list + delete in service) — delete depends on list

---

## Parallel Example: User Story 1

```bash
# Launch tests together (before implementation):
Task: "Unit test BackupService::create() in tests/Unit/Services/BackupServiceTest.php"
Task: "Feature test backup creation in tests/Feature/Admin/BackupTest.php"

# Launch Filament resource files together:
Task: "Create BackupsResource in app/Filament/Admin/Resources/Backups/BackupsResource.php"
Task: "Create BackupsTable in app/Filament/Admin/Resources/Backups/Tables/BackupsTable.php"
Task: "Create ListBackups page in app/Filament/Admin/Resources/Backups/Pages/ListBackups.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001–T004)
2. Complete Phase 2: Foundational (T005–T008)
3. Complete Phase 3: User Story 1 (T009–T016)
4. **STOP and VALIDATE**: Create → List → Download cycle via `/admin/backups`
5. Deploy/demo — MVP delivers backup creation + download

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. Add User Story 1 (MVP) → Test → Demo
3. Add User Story 2 → Test → Deploy
4. Add User Story 3 → Test
5. Polish → Finalize

### Parallel Team Strategy

With multiple developers:
1. Team completes Phase 1 + Phase 2 together
2. Once Phase 2 done:
   - Developer A: User Story 1 (create/download backup)
   - Developer B: User Story 2 (history management)
   - Developer C: User Story 3 (error handling)
3. Developer A finishes first → MVP deliverable
4. Developer B and C integrate after A's service is stable

---

## Summary

| Story | Priority | Tasks | Tests | Independent Test |
|-------|----------|-------|-------|-----------------|
| US1: Create & Download | P1 | 8 (T009–T016) | T009, T010 | Visit `/admin/backups`, create backup, download |
| US2: Manage History | P2 | 7 (T017–T023) | T017, T018 | List backups, delete one, verify removal |
| US3: Error Handling | P2 | 6 (T024–T029) | T024, T025 | Simulate failure, verify error message |
| **Total** | | **33 tasks** | **6 tests** | |

**Suggested MVP**: Phase 1 + Phase 2 + Phase 3 (User Story 1) = **16 tasks**
