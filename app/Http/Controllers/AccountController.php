<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Currency;
use App\Http\Requests\AccountRequest;
use App\Services\PasscodeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    protected PasscodeService $passcodeService;

    public function __construct(PasscodeService $passcodeService)
    {
        $this->passcodeService = $passcodeService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Account::with('currency')
            ->where('user_id', Auth::id())
            ->withMax('transactions', 'transaction_date');

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->search;
            $isMysql = DB::connection()->getDriverName() === 'mysql';
            $ftQuery = null;

            if ($isMysql) {
                $words = preg_split('/[\s,]+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);
                $terms = [];
                foreach ($words as $word) {
                    $word = preg_replace('/[+\-><()~*@"\'\\\\:;!?]/', '', $word);
                    if (strlen($word) > 0) {
                        $terms[] = '+' . $word . '*';
                    }
                }
                $ftQuery = $terms ? implode(' ', $terms) : '+' . $search . '*';
            }

            $query->where(function ($q) use ($search, $isMysql, $ftQuery) {
                if ($isMysql && $ftQuery) {
                    $q->whereRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', [$ftQuery])
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                } else {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                }
                $q->orWhere('type', 'like', "%{$search}%")
                  ->orWhereHas('currency', function ($cq) use ($search) {
                      $cq->where('code', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by type
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by currency
        if ($request->filled('currency_id') && $request->currency_id !== 'all') {
            $query->where('currency_id', $request->currency_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by balance range
        if ($request->filled('balance_min')) {
            $query->where('balance', '>=', $request->balance_min);
        }

        if ($request->filled('balance_max')) {
            $query->where('balance', '<=', $request->balance_max);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'transacted_desc');
        switch ($sortBy) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'balance_asc':
                $query->orderBy('balance', 'asc');
                break;
            case 'balance_desc':
                $query->orderBy('balance', 'desc');
                break;
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'created_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'type_asc':
                $query->orderBy('type', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'transacted_asc':
                $query->orderByRaw('COALESCE(transactions_max_transaction_date, \'1970-01-01\') ASC');
                break;
            case 'transacted_desc':
            default:
                $query->orderByRaw('COALESCE(transactions_max_transaction_date, \'1970-01-01\') DESC');
                break;
        }

        $accounts = $query->get();

        $currencies = Currency::orderBy('code')->get();

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'currencies' => $currencies,
            'filters' => $request->only(['search', 'type', 'currency_id', 'status', 'balance_min', 'balance_max', 'sort_by']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currencies = Currency::active()->get();

        return Inertia::render('Accounts/Create', [
            'currencies' => $currencies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountRequest $request)
    {
        $validated = $request->getValidatedDataForCreation();

        $account = Account::create($validated);

        return Inertia::location(route('accounts.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Account $account)
    {
        // Verify ownership
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if account is locked and passcode not verified
        if ($account->is_locked && $this->passcodeService->hasPasscode() && !$this->passcodeService->verified()) {
            $account->load('currency');

            return Inertia::render('Accounts/Show', [
                'account' => $account,
                'accounts' => Account::with('currency')->where('user_id', Auth::id())->orderBy('name')->get(),
                'currencies' => Currency::orderBy('code')->get(),
                'locked' => true,
            ]);
        }

        // Load account with currency
        $account->load('currency');

        // Pagination params
        $page = (int) $request->get('page', 1);
        $perPage = 10;

        // Get all transactions related to this account (both outgoing and incoming transfers)
        $outgoingTransactions = $account->transactions()
            ->with(['account.currency', 'transferToAccount.currency'])
            ->get();

        $incomingTransfers = \App\Models\Transaction::where('transfer_to_account_id', $account->id)
            ->where('user_id', Auth::id())
            ->with(['account.currency', 'transferToAccount.currency'])
            ->get();

        // Combine and sort all transactions
        $allTransactions = $outgoingTransactions->concat($incomingTransfers)
            ->sortByDesc('transaction_date')
            ->values();

        $total = $allTransactions->count();
        $lastPage = (int) max(ceil($total / $perPage), 1);

        // Slice the current page
        $pageTransactions = $allTransactions->forPage($page, $perPage)->values();

        // Add a flag to distinguish incoming transfers for UI purposes
        $pageTransactions = $pageTransactions->map(function ($transaction) use ($account) {
            $transaction->is_incoming_transfer = $transaction->transfer_to_account_id == $account->id;
            return $transaction;
        });

        // Manually set the transactions relationship
        $account->setRelation('transactions', $pageTransactions);

        // Get all user accounts for the transaction modal
        $accounts = Account::with('currency')
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        // Get currencies for the account edit modal
        $currencies = Currency::orderBy('code')->get();

        return Inertia::render('Accounts/Show', [
            'account' => $account,
            'accounts' => $accounts,
            'currencies' => $currencies,
            'transactionsMeta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $account)
    {
        $currencies = Currency::active()->get();

        return Inertia::render('Accounts/Edit', [
            'account' => $account->load('currency'),
            'currencies' => $currencies,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountRequest $request, Account $account)
    {
        // Check if the account belongs to the authenticated user
        if ($account->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Verify passcode if user has one configured
        if ($this->passcodeService->hasPasscode() && !$this->passcodeService->verify($request->passcode ?? '')) {
            return back()->withErrors(['passcode' => 'Invalid passcode.']);
        }

        $validatedData = $request->getValidatedDataForUpdate();



        $account->update($validatedData);

        return redirect()->back()
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Toggle hide_balance on an account (no passcode required).
     */
    public function toggleHideBalance(Account $account)
    {
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $account->update([
            'hide_balance' => !$account->hide_balance,
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        // Verify ownership
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if account has transactions
        if ($account->transactions()->count() > 0) {
            return redirect()->route('accounts.show', $account)
                ->with('error', 'Cannot delete account with existing transactions. Please delete all transactions first.');
        }

        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
