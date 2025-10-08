<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getModelAttribute()
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }
        return null;
    }

    public static function log(string $action, Model $model, array $oldValues = null, array $newValues = null, string $description = null): void
    {
        if (!Auth::check()) {
            return;
        }

        static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description ?? static::generateDescription($action, $model),
        ]);
    }

    private static function generateDescription(string $action, Model $model): string
    {
        $modelName = class_basename($model);
        $identifier = $model->name ?? $model->code ?? $model->email ?? $model->id;
        
        return match($action) {
            'created' => "Created {$modelName}: {$identifier}",
            'updated' => "Updated {$modelName}: {$identifier}",
            'deleted' => "Deleted {$modelName}: {$identifier}",
            'restored' => "Restored {$modelName}: {$identifier}",
            'force_deleted' => "Permanently deleted {$modelName}: {$identifier}",
            default => "{$action} {$modelName}: {$identifier}",
        };
    }
}
