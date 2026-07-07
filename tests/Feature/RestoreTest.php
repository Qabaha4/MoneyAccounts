<?php

use App\Models\Backup;
use App\Services\BackupService;
use App\Services\RestoreService;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('backups');
});

it('throws exception when restoring an incomplete backup', function () {
    $backup = Backup::factory()->processing()->create();
    $service = app(RestoreService::class);

    $service->restore($backup);
})->throws(\RuntimeException::class, 'Cannot restore from an incomplete backup.');

it('throws exception when restoring a failed backup', function () {
    $backup = Backup::factory()->failed()->create();
    $service = app(RestoreService::class);

    $service->restore($backup);
})->throws(\RuntimeException::class, 'Cannot restore from an incomplete backup.');

it('throws exception when backup file is missing from disk', function () {
    $backup = Backup::factory()->create(['file_path' => 'nonexistent.zip']);
    $service = app(RestoreService::class);

    $service->restore($backup);
})->throws(\RuntimeException::class, 'Backup file not found on disk.');
