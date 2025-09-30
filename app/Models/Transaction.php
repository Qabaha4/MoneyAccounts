<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'type',
        'amount',
        'description',
        'notes',
        'category',
        'reference_number',
        'transaction_date',
        'transfer_to_account_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'transaction_date' => 'date',
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

        static::creating(function ($transaction) {
            if (Auth::check() && !$transaction->user_id) {
                $transaction->user_id = Auth::id();
            }
        });

        // Update account balance after transaction changes
        static::created(function ($transaction) {
            $transaction->load('account');
            if ($transaction->account) {
                $transaction->account->updateBalance();
            }
            if ($transaction->transfer_to_account_id) {
                $transaction->load('transferToAccount');
                if ($transaction->transferToAccount) {
                    $transaction->transferToAccount->updateBalance();
                }
            }
        });

        static::updated(function ($transaction) {
            $transaction->load('account');
            if ($transaction->account) {
                $transaction->account->updateBalance();
            }
            if ($transaction->transfer_to_account_id) {
                $transaction->load('transferToAccount');
                if ($transaction->transferToAccount) {
                    $transaction->transferToAccount->updateBalance();
                }
            }
            // Update old account if account_id changed
            if ($transaction->isDirty('account_id')) {
                $oldAccountId = $transaction->getOriginal('account_id');
                if ($oldAccountId) {
                    $oldAccount = Account::find($oldAccountId);
                    if ($oldAccount) {
                        $oldAccount->updateBalance();
                    }
                }
            }
        });

        static::deleted(function ($transaction) {
            $transaction->load('account');
            if ($transaction->account) {
                $transaction->account->updateBalance();
            }
            if ($transaction->transfer_to_account_id) {
                $transaction->load('transferToAccount');
                if ($transaction->transferToAccount) {
                    $transaction->transferToAccount->updateBalance();
                }
            }
        });
    }

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account this transaction belongs to
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the transfer destination account (for transfer transactions)
     */
    public function transferToAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transfer_to_account_id');
    }

    /**
     * Scope for income transactions
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope for expense transactions
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope for transfer transactions
     */
    public function scopeTransfer($query)
    {
        return $query->where('type', 'transfer');
    }
}
