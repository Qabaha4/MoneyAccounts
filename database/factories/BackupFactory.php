<?php

namespace Database\Factories;

use App\Models\Backup;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupFactory extends Factory
{
    protected $model = Backup::class;

    public function definition(): array
    {
        return [
            'filename' => 'moneyaccounts-backup-' . now()->format('Ymd-His') . '-' . fake()->bothify('????') . '.zip',
            'file_path' => 'moneyaccounts-backup-' . now()->format('Ymd-His') . '-' . fake()->bothify('????') . '.zip',
            'file_size' => fake()->numberBetween(1024, 10485760),
            'status' => Backup::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Backup::STATUS_FAILED,
            'file_size' => null,
            'completed_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Backup::STATUS_PROCESSING,
            'file_size' => null,
            'completed_at' => null,
        ]);
    }
}
