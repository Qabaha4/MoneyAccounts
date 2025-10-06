<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
        'decimal_places',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
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
