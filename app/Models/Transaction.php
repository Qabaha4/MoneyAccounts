<?php

namespace App\Models;

use Carbon\Carbon;
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
        'exchange_rate',
        'converted_amount',
        'exchange_rate_source',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'transaction_date' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'converted_amount' => 'decimal:4',
    ];

    /**
     * Get the transaction date converted to application timezone
     * Returns ISO string format for frontend compatibility
     */
    public function getTransactionDateAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value, 'UTC') // Parse as UTC (stored format)
            ->setTimezone(config('app.timezone')) // Convert to app timezone
            ->toISOString(); // Return as ISO string for frontend
    }


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

    /**
     * Check if this is a cross-currency transfer
     */
    public function isCrossCurrencyTransfer(): bool
    {
        if ($this->type !== 'transfer' || !$this->transfer_to_account_id) {
            return false;
        }

        $this->load(['account.currency', 'transferToAccount.currency']);
        
        return $this->account->currency_id !== $this->transferToAccount->currency_id;
    }

    /**
     * Get the effective amount for the destination account
     * Returns converted amount if cross-currency, otherwise the original amount
     */
    public function getEffectiveAmount(): float
    {
        if ($this->isCrossCurrencyTransfer() && $this->converted_amount) {
            return (float) $this->converted_amount;
        }
        
        return (float) $this->amount;
    }

    /**
     * Calculate exchange rate from amount and converted amount
     */
    public function calculateExchangeRate(): ?float
    {
        if (!$this->converted_amount || !$this->amount) {
            return null;
        }
        
        return (float) $this->converted_amount / (float) $this->amount;
    }
}
