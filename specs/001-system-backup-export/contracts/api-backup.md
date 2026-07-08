# Download Contract: Backup Download

Filament v4 handles the admin UI (table, list, actions) automatically via the `BackupResource`. The only external-facing contract is the **backup download endpoint**, which streams the ZIP file.

## GET /admin/backups/{record}/download

Streams the backup ZIP archive for download.

### Response `200 OK`

- Content-Type: `application/zip`
- Content-Disposition: `attachment; filename="moneyaccounts-backup-{timestamp}-{random}.zip"`
- Content-Length: `{file_size}`
- Body: raw binary stream of the ZIP file

### Response `404 Not Found`

Standard Filament 404 page.

### Notes

- This endpoint is registered via the custom `DownloadAction` on the Filament table row
- The download must use Laravel's streaming response to handle large files without memory exhaustion
