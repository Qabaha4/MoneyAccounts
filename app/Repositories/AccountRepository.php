<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\Currency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountRepository
{
    /**
     * Get base query for user accounts.
     */
    private function baseQuery(): Builder
    {
        return Account::with('currency')
            ->where('user_id', Auth::id());
    }

    /**
     * Find account by ID for authenticated user.
     */
    public function findById(int $id): ?Account
    {
        return $this->baseQuery()
            ->where('id', $id)
            ->first();
    }

    /**
     * Find account by ID with transactions.
     */
    public function findByIdWithTransactions(int $id): ?Account
    {
        return $this->baseQuery()
            ->with(['transactions' => function ($query) {
                $query->orderBy('transaction_date', 'desc')
                      ->orderBy('created_at', 'desc');
            }])
            ->where('id', $id)
            ->first();
    }

    /**
     * Get all accounts for authenticated user.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = $this->baseQuery();

        $this->applyFilters($query, $filters);

        return $query->orderBy('name')->get();
    }

    /**
     * Get paginated accounts.
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        $this->applyFilters($query, $filters);

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get active accounts only.
     */
    public function getActive(): Collection
    {
        return $this->baseQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get accounts by type.
     */
    public function getByType(string $type): Collection
    {
        return $this->baseQuery()
            ->where('type', $type)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get accounts by currency.
     */
    public function getByCurrency(int $currencyId): Collection
    {
        return $this->baseQuery()
            ->where('currency_id', $currencyId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Search accounts by name or description.
     */
    public function search(string $term): Collection
    {
        return $this->baseQuery()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                      ->orWhere('description', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get accounts with balance greater than specified amount.
     */
    public function getWithBalanceGreaterThan(float $amount): Collection
    {
        return $this->baseQuery()
            ->where('balance', '>', $amount)
            ->orderBy('balance', 'desc')
            ->get();
    }

    /**
     * Get accounts with balance less than specified amount.
     */
    public function getWithBalanceLessThan(float $amount): Collection
    {
        return $this->baseQuery()
            ->where('balance', '<', $amount)
            ->orderBy('balance', 'asc')
            ->get();
    }

    /**
     * Get accounts with recent activity.
     */
    public function getWithRecentActivity(int $days = 30): Collection
    {
        return $this->baseQuery()
            ->whereHas('transactions', function ($query) use ($days) {
                $query->where('transaction_date', '>=', now()->subDays($days));
            })
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get accounts without any transactions.
     */
    public function getWithoutTransactions(): Collection
    {
        return $this->baseQuery()
            ->whereDoesntHave('transactions')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new account.
     */
    public function create(array $data): Account
    {
        return Account::create($data);
    }

    /**
     * Update an account.
     */
    public function update(Account $account, array $data): bool
    {
        return $account->update($data);
    }

    /**
     * Delete an account.
     */
    public function delete(Account $account): bool
    {
        return $account->delete();
    }

    /**
     * Check if account name is unique for user.
     */
    public function isNameUnique(string $name, ?int $excludeId = null): bool
    {
        $query = Account::where('user_id', Auth::id())
            ->where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Get account statistics.
     */
    public function getStatistics(): array
    {
        $accounts = $this->getAll();

        return [
            'total_count' => $accounts->count(),
            'active_count' => $accounts->where('is_active', true)->count(),
            'inactive_count' => $accounts->where('is_active', false)->count(),
            'total_balance' => $accounts->sum('balance'),
            'average_balance' => $accounts->avg('balance'),
            'by_type' => $accounts->groupBy('type')->map->count(),
            'by_currency' => $accounts->groupBy('currency.code')->map->count(),
        ];
    }

    /**
     * Get balance summary by currency.
     */
    public function getBalanceSummaryByCurrency(): Collection
    {
        return $this->baseQuery()
            ->selectRaw('currency_id, SUM(balance) as total_balance, COUNT(*) as account_count')
            ->groupBy('currency_id')
            ->with('currency')
            ->get();
    }

    /**
     * Get balance summary by type.
     */
    public function getBalanceSummaryByType(): Collection
    {
        return $this->baseQuery()
            ->selectRaw('type, SUM(balance) as total_balance, COUNT(*) as account_count')
            ->groupBy('type')
            ->get();
    }

    /**
     * Get accounts that need balance updates.
     */
    public function getAccountsNeedingBalanceUpdate(): Collection
    {
        return $this->baseQuery()
            ->whereHas('transactions', function ($query) {
                $query->where('updated_at', '>', DB::raw('accounts.updated_at'));
            })
            ->get();
    }

    /**
     * Bulk update account balances.
     */
    public function bulkUpdateBalances(Collection $accounts): void
    {
        foreach ($accounts as $account) {
            $account->updateBalance();
        }
    }

    /**
     * Apply filters to query.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        if (isset($filters['min_balance'])) {
            $query->where('balance', '>=', $filters['min_balance']);
        }

        if (isset($filters['max_balance'])) {
            $query->where('balance', '<=', $filters['max_balance']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (isset($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }
    }

    /**
     * Get accounts for transfer operations (excluding specified account).
     */
    public function getForTransfer(int $excludeAccountId, ?int $currencyId = null): Collection
    {
        $query = $this->baseQuery()
            ->where('is_active', true)
            ->where('id', '!=', $excludeAccountId);

        if ($currencyId) {
            $query->where('currency_id', $currencyId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get recently updated accounts.
     */
    public function getRecentlyUpdated(int $limit = 10): Collection
    {
        return $this->baseQuery()
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get accounts with low balance (configurable threshold).
     */
    public function getLowBalanceAccounts(float $threshold = 100): Collection
    {
        return $this->baseQuery()
            ->where('is_active', true)
            ->where('balance', '<', $threshold)
            ->where('balance', '>', 0) // Exclude negative balances
            ->orderBy('balance', 'asc')
            ->get();
    }
}