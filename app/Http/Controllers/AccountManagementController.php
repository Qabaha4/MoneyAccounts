<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Services\AccountService;
use App\Repositories\AccountRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AccountManagementController extends Controller
{
    protected AccountService $accountService;
    protected AccountRepository $accountRepository;

    public function __construct(
        AccountService $accountService,
        AccountRepository $accountRepository
    ) {
        $this->accountService = $accountService;
        $this->accountRepository = $accountRepository;
    }

    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only([
            'active', 'type', 'currency_id', 'min_balance', 
            'max_balance', 'search', 'created_after', 'created_before'
        ]);

        $accounts = $this->accountRepository->getPaginated(15, $filters);
        $statistics = $this->accountRepository->getStatistics();

        return view('accounts.index', compact('accounts', 'statistics'));
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(): View
    {
        $currencies = $this->accountService->getAvailableCurrencies();
        $accountTypes = $this->accountService->getAccountTypes();

        return view('accounts.create', compact('currencies', 'accountTypes'));
    }

    /**
     * Store a newly created account.
     */
    public function store(AccountRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $validatedData = $request->getValidatedDataForCreation();
            $account = $this->accountService->createAccount($validatedData);

            return redirect()
                ->route('accounts.show', $account)
                ->with('success', 'Account created successfully');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create account: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified account.
     */
    public function show(int $id): View|JsonResponse
    {
        $account = $this->accountRepository->findByIdWithTransactions($id);

        if (!$account) {
            abort(404);
        }

        $accountSummary = $this->accountService->getAccountSummary($account);

        return view('accounts.show', compact('account', 'accountSummary'));
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(int $id): View
    {
        $account = $this->accountRepository->findById($id);

        if (!$account) {
            abort(404);
        }

        $currencies = $this->accountService->getAvailableCurrencies();
        $accountTypes = $this->accountService->getAccountTypes();

        return view('accounts.edit', compact('account', 'currencies', 'accountTypes'));
    }

    /**
     * Update the specified account.
     */
    public function update(AccountRequest $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            $account = $this->accountRepository->findById($id);

            if (!$account) {
                abort(404);
            }

            $validatedData = $request->getValidatedDataForUpdate();
            $updatedAccount = $this->accountService->updateAccount($account, $validatedData);

            return redirect()
                ->route('accounts.show', $updatedAccount)
                ->with('success', 'Account updated successfully');

        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update account: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified account.
     */
    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        try {
            $account = $this->accountRepository->findById($id);

            if (!$account) {
                if (request()->expectsJson()) {
                    return response()->json(['error' => 'Account not found'], 404);
                }
                abort(404);
            }

            $this->accountService->deleteAccount($account);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Account deleted successfully'
                ]);
            }

            return redirect()
                ->route('accounts.index')
                ->with('success', 'Account deleted successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete account: ' . $e->getMessage()
                ], 422);
            }

            return back()
                ->withErrors(['error' => 'Failed to delete account: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle account active status.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $account = $this->accountRepository->findById($id);

            if (!$account) {
                return response()->json(['error' => 'Account not found'], 404);
            }

            if ($account->is_active) {
                $this->accountService->deactivateAccount($account);
                $message = 'Account deactivated successfully';
            } else {
                $this->accountService->activateAccount($account);
                $message = 'Account activated successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'account' => $account->fresh()->load('currency')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle account status: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get account statistics.
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->accountRepository->getStatistics();
        $balanceByCurrency = $this->accountRepository->getBalanceSummaryByCurrency();
        $balanceByType = $this->accountRepository->getBalanceSummaryByType();

        return response()->json([
            'statistics' => $statistics,
            'balance_by_currency' => $balanceByCurrency,
            'balance_by_type' => $balanceByType
        ]);
    }

    /**
     * Search accounts.
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->input('term', '');
        
        if (empty($term)) {
            return response()->json(['accounts' => []]);
        }

        $accounts = $this->accountRepository->search($term);

        return response()->json(['accounts' => $accounts]);
    }

    /**
     * Get accounts for transfer operations.
     */
    public function getForTransfer(Request $request): JsonResponse
    {
        $excludeId = $request->input('exclude_id');
        $currencyId = $request->input('currency_id');

        if (!$excludeId) {
            return response()->json(['error' => 'exclude_id is required'], 422);
        }

        $accounts = $this->accountRepository->getForTransfer($excludeId, $currencyId);

        return response()->json(['accounts' => $accounts]);
    }

    /**
     * Get low balance accounts.
     */
    public function lowBalance(Request $request): JsonResponse
    {
        $threshold = $request->input('threshold', 100);
        $accounts = $this->accountRepository->getLowBalanceAccounts($threshold);

        return response()->json(['accounts' => $accounts]);
    }

    /**
     * Bulk update account balances.
     */
    public function bulkUpdateBalances(): JsonResponse
    {
        try {
            $accounts = $this->accountRepository->getAccountsNeedingBalanceUpdate();
            $this->accountRepository->bulkUpdateBalances($accounts);

            return response()->json([
                'success' => true,
                'message' => "Updated {$accounts->count()} account balances",
                'updated_count' => $accounts->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update balances: ' . $e->getMessage()
            ], 500);
        }
    }
}