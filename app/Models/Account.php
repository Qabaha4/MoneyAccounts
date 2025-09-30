<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{
    protected $fillable = [
        'user_id',
        'currency_id',
        'name',
        'description',
        'type',
        'balance',
        'initial_balance',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'initial_balance' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Global scope to automatically filter by authenticated user (tenant scoping)
     */
    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        });

        static::creating(function ($account) {
            if (Auth::check() && !$account->user_id) {
                $account->user_id = Auth::id();
            }
        });
    }

    /**
     * Get the user that owns the account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the currency for this account
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get all transactions for this account
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get transactions where this account is the transfer destination
     */
    public function transferTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_to_account_id');
    }

    /**
     * Scope to get only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate and update account balance based on transactions
     */
    public function updateBalance(): void
    {
        $transactionSum = $this->transactions()
            ->selectRaw('SUM(CASE 
                WHEN type = "income" THEN amount 
                WHEN type = "expense" THEN -amount 
                WHEN type = "transfer" THEN -amount 
                ELSE 0 
            END) as total')
            ->value('total') ?? 0;

        $transferInSum = $this->transferTransactions()
            ->where('type', 'transfer')
            ->sum('amount') ?? 0;

        $this->update([
            'balance' => $this->initial_balance + $transactionSum + $transferInSum
        ]);
    }
}
