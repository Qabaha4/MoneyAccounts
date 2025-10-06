<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Currency;
use App\Http\Requests\AccountRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountService
{
    /**
     * Get all accounts for the authenticated user.
     */
    public function getAllAccounts(bool $activeOnly = false): Collection
    {
        $query = Account::with('currency')
            ->where('user_id', Auth::id())
            ->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Get paginated accounts for the authenticated user.
     */
    public function getPaginatedAccounts(int $perPage = 15, bool $activeOnly = false): LengthAwarePaginator
    {
        $query = Account::with('currency')
            ->where('user_id', Auth::id())
            ->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get a specific account by ID for the authenticated user.
     */
    public function getAccountById(int $accountId): ?Account
    {
        return Account::with(['currency', 'transactions'])
            ->where('user_id', Auth::id())
            ->where('id', $accountId)
            ->first();
    }

    /**
     * Create a new account.
     */
    public function createAccount(array $data): Account
    {
        // Ensure user_id is set
        if (!isset($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }

        // Set initial balance as balance if not set
        if (isset($data['initial_balance']) && !isset($data['balance'])) {
            $data['balance'] = $data['initial_balance'];
        }

        return DB::transaction(function () use ($data) {
            $account = Account::create($data);

            // Log account creation activity
            $this->logAccountActivity($account, 'created');

            return $account->load('currency');
        });
    }

    /**
     * Update an existing account.
     */
    public function updateAccount(Account $account, array $data): Account
    {
        $this->ensureAccountOwnership($account);

        // Remove fields that shouldn't be updated directly
        unset($data['user_id'], $data['balance'], $data['initial_balance']);

        return DB::transaction(function () use ($account, $data) {
            $originalData = $account->toArray();

            $account->update($data);

            // Log account update activity
            $this->logAccountActivity($account, 'updated', $originalData);

            return $account->load('currency');
        });
    }

    /**
     * Soft delete an account (deactivate).
     */
    public function deactivateAccount(Account $account): bool
    {
        $this->ensureAccountOwnership($account);

        // Check if account has transactions
        if ($account->transactions()->count() > 0) {
            throw new \Exception('Cannot deactivate account with existing transactions. Please transfer or delete transactions first.');
        }

        return DB::transaction(function () use ($account) {
            $result = $account->update(['is_active' => false]);

            // Log account deactivation
            $this->logAccountActivity($account, 'deactivated');

            return $result;
        });
    }

    /**
     * Permanently delete an account.
     */
    public function deleteAccount(Account $account): bool
    {
        $this->ensureAccountOwnership($account);

        // Check if account has transactions
        if ($account->transactions()->count() > 0) {
            throw new \Exception('Cannot delete account with existing transactions. Please transfer or delete transactions first.');
        }

        return DB::transaction(function () use ($account) {
            // Log account deletion before deleting
            $this->logAccountActivity($account, 'deleted');

            return $account->delete();
        });
    }

    /**
     * Get account balance summary.
     */
    public function getAccountBalanceSummary(): array
    {
        $accounts = $this->getAllAccounts(true);

        $summary = [
            'total_accounts' => $accounts->count(),
            'total_balance' => 0,
            'by_currency' => [],
            'by_type' => []
        ];

        foreach ($accounts as $account) {
            $currencyCode = $account->currency->code;
            $accountType = $account->type;

            // Group by currency
            if (!isset($summary['by_currency'][$currencyCode])) {
                $summary['by_currency'][$currencyCode] = [
                    'currency' => $account->currency,
                    'total_balance' => 0,
                    'account_count' => 0
                ];
            }

            $summary['by_currency'][$currencyCode]['total_balance'] += $account->balance;
            $summary['by_currency'][$currencyCode]['account_count']++;

            // Group by type
            if (!isset($summary['by_type'][$accountType])) {
                $summary['by_type'][$accountType] = [
                    'type' => $accountType,
                    'total_balance' => 0,
                    'account_count' => 0
                ];
            }

            $summary['by_type'][$accountType]['total_balance'] += $account->balance;
            $summary['by_type'][$accountType]['account_count']++;
        }

        return $summary;
    }

    /**
     * Get available currencies for account creation.
     */
    public function getAvailableCurrencies(): Collection
    {
        return Currency::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get account types with their display names.
     */
    public function getAccountTypes(): array
    {
        return [
            'checking' => 'Checking Account',
            'savings' => 'Savings Account',
            'credit' => 'Credit Account',
            'investment' => 'Investment Account',
            'cash' => 'Cash Account',
            'other' => 'Other Account'
        ];
    }

    /**
     * Transfer funds between accounts.
     */
    public function transferFunds(Account $fromAccount, Account $toAccount, float $amount, ?string $description = null): bool
    {
        $this->ensureAccountOwnership($fromAccount);
        $this->ensureAccountOwnership($toAccount);

        if ($amount <= 0) {
            throw new \Exception('Transfer amount must be greater than zero.');
        }

        if ($fromAccount->id === $toAccount->id) {
            throw new \Exception('Cannot transfer to the same account.');
        }

        if ($fromAccount->currency_id !== $toAccount->currency_id) {
            throw new \Exception('Cannot transfer between accounts with different currencies.');
        }

        return DB::transaction(function () use ($fromAccount, $toAccount, $amount, $description) {
            // Create transfer transaction for source account
            $fromAccount->transactions()->create([
                'user_id' => Auth::id(),
                'type' => 'transfer',
                'amount' => $amount,
                'description' => $description ?? "Transfer to {$toAccount->name}",
                'transaction_date' => now(),
                'transfer_to_account_id' => $toAccount->id
            ]);

            // Update balances
            $fromAccount->updateBalance();
            $toAccount->updateBalance();

            return true;
        });
    }

    /**
     * Ensure the authenticated user owns the account.
     */
    private function ensureAccountOwnership(Account $account): void
    {
        if ($account->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized access to account.');
        }
    }

    /**
     * Log account activity for audit purposes.
     */
    private function logAccountActivity(Account $account, string $action, ?array $originalData = null): void
    {
        // This could be expanded to use Laravel's activity log package
        // or a custom logging system for audit trails
        Log::info("Account {$action}", [
            'user_id' => Auth::id(),
            'account_id' => $account->id,
            'account_name' => $account->name,
            'action' => $action,
            'original_data' => $originalData,
            'timestamp' => now()
        ]);
    }

    /**
     * Validate account data before operations.
     */
    public function validateAccountData(array $data): array
    {
        $errors = [];

        // Custom business logic validations
        if (isset($data['name']) && strlen(trim($data['name'])) < 2) {
            $errors['name'] = 'Account name must be at least 2 characters long.';
        }

        if (isset($data['currency_id'])) {
            $currency = Currency::find($data['currency_id']);
            if (!$currency || !$currency->is_active) {
                $errors['currency_id'] = 'Selected currency is not available.';
            }
        }

        if (isset($data['initial_balance']) && abs($data['initial_balance']) > 999999999.99) {
            $errors['initial_balance'] = 'Initial balance is too large.';
        }

        return $errors;
    }

    /**
     * Get account statistics for dashboard.
     */
    public function getDashboardStatistics(): array
    {
        $accounts = $this->getAllAccounts();

        return [
            'total_accounts' => $accounts->count(),
            'active_accounts' => $accounts->where('is_active', true)->count(),
            'inactive_accounts' => $accounts->where('is_active', false)->count(),
            'total_balance' => $accounts->sum('balance'),
            'average_balance' => $accounts->avg('balance'),
            'accounts_by_type' => $accounts->groupBy('type')->map->count(),
            'recent_activity' => $this->getRecentAccountActivity()
        ];
    }

    /**
     * Get account statistics for a specific account.
     */
    public function getAccountStatistics(Account $account): array
    {
        return [
            'total_transactions' => $account->transactions()->count(),
            'total_income' => $account->transactions()
                ->where('type', 'income')
                ->sum('amount'),
            'total_expense' => $account->transactions()
                ->where('type', 'expense')
                ->sum('amount'),
            'last_transaction_date' => $account->transactions()
                ->latest('transaction_date')
                ->value('transaction_date'),
            'average_transaction_amount' => $account->transactions()
                ->avg('amount'),
        ];
    }

    /**
     * Get comprehensive account summary.
     */
    public function getAccountSummary(Account $account): array
    {
        $statistics = $this->getAccountStatistics($account);

        return [
            'account' => $account,
            'statistics' => $statistics,
            'recent_transactions' => $account->transactions()
                ->latest('transaction_date')
                ->limit(10)
                ->get(),
            'balance_trend' => $this->getBalanceTrend($account),
        ];
    }

    /**
     * Activate an account.
     */
    public function activateAccount(Account $account): Account
    {
        $account->update(['is_active' => true]);

        Log::info('Account activated', [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'user_id' => $account->user_id,
        ]);

        return $account->fresh();
    }

    /**
     * Get balance trend for an account.
     */
    private function getBalanceTrend(Account $account): array
    {
        // Get transactions from last 30 days
        $transactions = $account->transactions()
            ->where('transaction_date', '>=', now()->subDays(30))
            ->orderBy('transaction_date')
            ->get();

        $trend = [];
        $runningBalance = $account->initial_balance;

        foreach ($transactions as $transaction) {
            $runningBalance += $transaction->amount;
            $trend[] = [
                'date' => $transaction->transaction_date,
                'balance' => $runningBalance,
                'transaction_amount' => $transaction->amount,
            ];
        }

        return $trend;
    }

    /**
     * Get recent account activity.
     */
    private function getRecentAccountActivity(): Collection
    {
        return Account::with('currency')
            ->where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
    }
}
