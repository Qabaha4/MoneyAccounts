<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait HasCustomId
{
    public static function bootHasCustomId(): void
    {
        static::creating(function ($model) {
            if ($model->getKey()) {
                return;
            }

            $period = static::customIdPeriod();
            $modelType = static::customIdModelType();
            $prefix = static::customIdPrefix();

            $number = DB::transaction(function () use ($period, $modelType) {
                $counter = DB::table('custom_id_counters')
                    ->where('model_type', $modelType)
                    ->where('period', $period)
                    ->lockForUpdate()
                    ->first();

                $next = ($counter->last_number ?? 0) + 1;

                if ($next > 999) {
                    throw new \RuntimeException(
                        "Custom ID sequence exhausted for {$modelType} in period {$period}"
                    );
                }

                DB::table('custom_id_counters')->updateOrInsert(
                    ['model_type' => $modelType, 'period' => $period],
                    ['last_number' => $next, 'created_at' => now(), 'updated_at' => now()]
                );

                return $next;
            });

            $model->setAttribute(
                $model->getKeyName(),
                $prefix . $period . str_pad($number, 3, '0', STR_PAD_LEFT)
            );
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    abstract public static function customIdPrefix(): string;

    abstract public static function customIdModelType(): string;

    public static function customIdPeriod(): string
    {
        return now()->format('y-m-d');
    }
}
