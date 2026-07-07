# System Backup Export

**Feature ID**: F-001
**Status**: Draft
**Priority**: Medium

## Overview

Administrators need the ability to export a complete backup of the entire system — including accounts, transactions, users, settings, and all related data — to safeguard against data loss and enable migration to other environments. This feature provides a dedicated admin panel page that allows authenticated administrators to generate, download, and manage full system backups with a single click.

## User Stories

- As an admin, I want to export a full backup of all system data so I can restore it in case of data loss.
- As an admin, I want to download the backup as a single archive file so I can store it securely off-site.
- As an admin, I want to see a list of previously generated backups with timestamps and sizes so I can track what has been saved.
- As an admin, I want to delete old backups from the server so I can free up storage space.
- As an admin, I want to be notified when a backup completes (or fails) so I can take appropriate action.

## Functional Requirements

### F-REQ-01: Admin Backup Panel Page
The system must provide a dedicated admin page accessible only to authenticated admin users, where all backup operations are managed.

**Acceptance Criteria**:
- The backup page is linked from the main navigation or settings area
- Non-admin users cannot access the page (return 403 or redirect)
- The page shows backup history, the "Create Backup" button, and per-backup actions

### F-REQ-02: Full Backup Generation
The system must generate a complete backup of all system data when the admin initiates it.

**Acceptance Criteria**:
- Backup includes all database records: users, accounts, transactions, currencies, settings, and any other application data
- Backup includes uploaded files (avatars, attachments, etc.)
- Backup is packaged as a single downloadable archive (e.g., ZIP)
- A progress indicator is shown during backup generation
- The backup filename includes a timestamp (e.g., `moneyaccounts-backup-20260707-143022.zip`)
- Duplicate backups are allowed (the system appends a counter or timestamp)

### F-REQ-03: Backup Download
The system must allow the admin to download any completed backup archive.

**Acceptance Criteria**:
- Each backup entry in the history list has a "Download" button
- Clicking download initiates the file download immediately
- The download file is the original archive, not re-compressed
- Download works for backups of any size (streaming download)

### F-REQ-04: Backup Deletion
The system must allow the admin to delete individual backup archives from the server.

**Acceptance Criteria**:
- Each backup entry has a "Delete" button
- Clicking delete shows a confirmation dialog before proceeding
- After deletion, the backup is removed from the list and the archive file is deleted from storage
- The backup history list updates immediately

### F-REQ-05: Backup History
The system must display a list of all previously generated backups with metadata.

**Acceptance Criteria**:
- Each entry shows: filename, file size, creation date/time, status (completed/failed)
- List is ordered by creation date (newest first)
- If no backups exist, a friendly empty state message is shown
- The list supports pagination if there are more than 20 backups

### F-REQ-06: Error Handling & Notifications
The system must handle errors gracefully and inform the admin of success or failure.

**Acceptance Criteria**:
- If backup generation fails, an error message explaining the reason is shown
- Successful backup generation shows a success notification
- Network or timeout errors during download show an appropriate message
- Server-side errors (disk full, permission denied) are logged and displayed

### F-REQ-07: Storage Management
The system must manage backup storage responsibly.

**Acceptance Criteria**:
- Backups are stored in a dedicated directory separate from application code
- If the storage disk is nearly full (>90%), a warning is displayed before generating a new backup
- Admins are warned if a backup file exceeds a reasonable size threshold

## User Scenarios & Testing

### Scenario 1: Successful backup creation and download
1. Admin navigates to Backup panel
2. Page loads showing empty backup history with "No backups yet" message
3. Admin clicks "Create Backup" button
4. System shows progress indicator
5. After completion, success notification appears
6. The new backup appears at the top of the history list with filename, size, and timestamp
7. Admin clicks "Download" on the new backup
8. The backup archive downloads to the admin's local machine

### Scenario 2: Backup generation failure
1. Admin navigates to Backup panel
2. Admin clicks "Create Backup"
3. Backup generation encounters an error (e.g., insufficient disk space)
4. Error notification is displayed with the reason
5. No new entry is added to the backup history
6. The system logs the error details

### Scenario 3: Delete a backup
1. Admin views backup history with multiple entries
2. Admin clicks "Delete" on an existing backup
3. Confirmation dialog appears: "Are you sure you want to delete this backup?"
4. Admin confirms
5. The backup is removed from the list
6. Success notification is shown

## Success Criteria

| Criterion | Measure |
|-----------|---------|
| Backup generation completes within 30 seconds for typical datasets (<100MB) | Time |
| All database tables are represented in the backup output | Completeness |
| Downloaded archive opens successfully with standard archive tools | Usability |
| Admin can complete the full flow (create → download → delete) in under 2 minutes | UX efficiency |
| Backups are listed with correct filename, size, and date metadata | Accuracy |
| Error states (disk full, permissions, timeout) show clear messages to the user | Error handling |

## Key Entities

- **Backup**: A single backup record with ID, filename, file path, file size, status, created_at, and optionally a checksum
- **BackupArchive**: The actual file stored on disk (ZIP archive)
- **BackupHistory**: The list/table of backup records displayed to the admin

## Out of Scope

- Automated/scheduled backup generation
- One-click restore from backup (manual restore only)
- Incremental or differential backups
- Cloud storage integration (S3, Dropbox, etc.)
- Email notification of backup status
- Multi-user role management for backups (single admin role)

## Assumptions

- There is exactly one admin role; any authenticated user with admin privileges can access backups
- Backups are stored on the local filesystem in a configurable directory
- The system has sufficient disk space for at least 3 full backups before warnings appear
- PHP's ZIP extension or equivalent is available on the server
- Database size is <1GB for typical usage of this application

## Dependencies

- Server must have sufficient disk space for backup storage
- PHP must have ZIP extension enabled
- Write permission on the backup storage directory
- Database user must have SELECT access on all tables
