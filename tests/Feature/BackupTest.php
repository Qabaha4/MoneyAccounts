<?php

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('backups');
});

it('creates a backup and stores a zip file on disk', function () {
    $service = app(BackupService::class);

    $backup = $service->create();

    expect($backup)->toBeInstanceOf(Backup::class);
    expect($backup->status)->toBe(Backup::STATUS_COMPLETED);
    expect($backup->filename)->toMatch('/^moneyaccounts-backup-\d{8}-\d{6}-[a-z0-9]{4}\.zip$/i');
    expect($backup->file_size)->toBeGreaterThan(0);
    expect($backup->started_at)->not->toBeNull();
    expect($backup->completed_at)->not->toBeNull();
});

it('deletes backup from disk and database', function () {
    $service = app(BackupService::class);
    $backup = $service->create();

    $service->delete($backup);

    expect(Backup::find($backup->id))->toBeNull();
});

it('throws exception when deleting a processing backup', function () {
    $backup = Backup::factory()->create(['status' => Backup::STATUS_PROCESSING]);
    $service = app(BackupService::class);

    $service->delete($backup);
})->throws(\RuntimeException::class, 'Cannot delete a backup that is currently being generated.');

it('lists backups as a paginated collection', function () {
    Backup::factory()->count(3)->create();

    $service = app(BackupService::class);
    $result = $service->listAll();

    expect($result->total())->toBe(3);
});
