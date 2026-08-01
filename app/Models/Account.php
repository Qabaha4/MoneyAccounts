<?php

namespace App\Models;

use App\Models\Concerns\HasCustomId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{
    use HasCustomId, HasFactory;

    public static function customIdPrefix(): string
    {
        return 'acc-';
    }

    public static function customIdModelType(): string
    {
        return 'account';
    }

    public static function customIdPeriod(): string
    {
        return now()->format('ym');
    }
    protected $fillable = [
        'user_id',
        'currency_id',
        'name',
        'description',
        'type',
        'balance',
        'initial_balance',
        'is_active',
        'is_locked',
        'hide_balance',
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'initial_balance' => 'decimal:4',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'hide_balance' => 'boolean',
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

        static::created(function ($account) {
            if (Auth::check()) {
                Activity::log('created', $account, Auth::user(), "Created account '{$account->name}'");
            }
        });

        static::updated(function ($account) {
            if (Auth::check()) {
                $dirty = collect($account->getDirty())->except(['balance', 'updated_at']);
                if ($dirty->isEmpty()) {
                    return;
                }
                $changes = $dirty->mapWithKeys(fn ($new, $field) => [
                    $field => match ($field) {
                        'currency_id' => [
                            'from' => optional(Currency::find($account->getOriginal($field)))->code ?? $account->getOriginal($field),
                            'to' => optional(Currency::find($new))->code ?? $new,
                        ],
                        default => [
                            'from' => $account->getOriginal($field),
                            'to' => $new,
                        ],
                    },
                ])->all();
                Activity::log('updated', $account, Auth::user(), "Updated account '{$account->name}'", [
                    'changes' => $changes,
                ]);
            }
        });

        static::deleted(function ($account) {
            if (Auth::check()) {
                Activity::log('deleted', $account, Auth::user(), "Deleted account '{$account->name}'");
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

        // For incoming transfers, use converted_amount if it's a cross-currency transfer
        $transferInSum = $this->transferTransactions()
            ->where('type', 'transfer')
            ->selectRaw('SUM(CASE 
                WHEN converted_amount IS NOT NULL THEN converted_amount 
                ELSE amount 
            END) as total')
            ->value('total') ?? 0;

        $this->update([
            'balance' => $this->initial_balance + $transactionSum + $transferInSum
        ]);
    }
}
