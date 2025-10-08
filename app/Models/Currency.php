<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            AuditLog::log('created', $model);
        });

        static::updated(function ($model) {
            AuditLog::log('updated', $model, $model->getOriginal(), $model->getAttributes());
        });

        static::deleted(function ($model) {
            AuditLog::log('deleted', $model);
        });
    }
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
        'decimal_places',
        'exchange_rate',
        'rate_source',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
        'exchange_rate' => 'decimal:6',
    ];

    /**
     * Get all accounts using this currency
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Scope to get only active currencies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
