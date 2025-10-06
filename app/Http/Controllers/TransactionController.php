<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get all user account IDs for incoming transfer detection
        $userAccountIds = Account::where('user_id', Auth::id())->pluck('id')->toArray();

        // Build base query for outgoing transactions and incoming transfers
        $baseQuery = Transaction::with(['account.currency', 'transferToAccount.currency'])
            ->where(function ($q) use ($userAccountIds) {
                // Outgoing transactions (user's own transactions)
                $q->whereHas('account', function ($accountQuery) {
                    $accountQuery->where('user_id', Auth::id());
                })
                // Incoming transfers (transfers to user's accounts)
                ->orWhere(function ($transferQuery) use ($userAccountIds) {
                    $transferQuery->whereIn('transfer_to_account_id', $userAccountIds)
                        ->where('type', 'transfer');
                });
            });

        // Filter by account if specified
        if ($request->filled('account_id')) {
            $baseQuery->where(function ($q) use ($request) {
                $q->where('account_id', $request->account_id)
                  ->orWhere('transfer_to_account_id', $request->account_id);
            });
        }

        // Filter by type if specified
        if ($request->filled('type')) {
            $baseQuery->where('type', $request->type);
        }

        // Filter by search term if specified
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $baseQuery->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', "%{$searchTerm}%")
                    ->orWhere('amount', 'like', "%{$searchTerm}%")
                    ->orWhereHas('account', function ($accountQuery) use ($searchTerm) {
                        $accountQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('transferToAccount', function ($transferAccountQuery) use ($searchTerm) {
                        $transferAccountQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Filter by date range if specified
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $baseQuery->whereDate('transaction_date', '<=', $request->date_to);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'transaction_date_desc');
        switch ($sortBy) {
            case 'transaction_date_asc':
                $baseQuery->orderBy('transaction_date', 'asc');
                break;
            case 'amount_desc':
                $baseQuery->orderBy('amount', 'desc');
                break;
            case 'amount_asc':
                $baseQuery->orderBy('amount', 'asc');
                break;
            case 'type_asc':
                $baseQuery->orderBy('type', 'asc');
                break;
            case 'description_asc':
                $baseQuery->orderBy('description', 'asc');
                break;
            case 'transaction_date_desc':
            default:
                $baseQuery->orderBy('transaction_date', 'desc');
                break;
        }

        // Get paginated results
        $transactions = $baseQuery->paginate(20);

        // Add is_incoming_transfer flag to each transaction
        $transactions->getCollection()->transform(function ($transaction) use ($userAccountIds) {
            $transaction->is_incoming_transfer = in_array($transaction->transfer_to_account_id, $userAccountIds) 
                && !in_array($transaction->account_id, $userAccountIds);
            return $transaction;
        });

        $accounts = Account::with('currency')
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'filters' => $request->only(['account_id', 'type', 'search', 'sort_by', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = Account::where('user_id', Auth::id())->with('currency')->get();

        return Inertia::render('Transactions/Create', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'transfer_to_account_id' => 'nullable|exists:accounts,id|different:account_id',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'converted_amount' => 'nullable|numeric|min:0.01',
            'exchange_rate_source' => 'nullable|string|in:manual,api',
        ]);

        // Verify account ownership
        $account = Account::where('id', $validated['account_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Verify transfer account ownership if applicable
        if ($validated['type'] === 'transfer' && $validated['transfer_to_account_id']) {
            Account::where('id', $validated['transfer_to_account_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();
            
            // Check if this is a cross-currency transfer
            $sourceAccount = Account::with('currency')->find($validated['account_id']);
            $destinationAccount = Account::with('currency')->find($validated['transfer_to_account_id']);
            
            if ($sourceAccount->currency_id !== $destinationAccount->currency_id) {
                // Cross-currency transfer requires exchange rate and converted amount
                if (!$validated['exchange_rate'] || !$validated['converted_amount']) {
                    return back()->withErrors([
                        'exchange_rate' => 'Exchange rate is required for cross-currency transfers.',
                        'converted_amount' => 'Converted amount is required for cross-currency transfers.'
                    ]);
                }
            }
        }

        // Parse ISO string from frontend (user's timezone) and convert to UTC for storage
        $validated['transaction_date'] = Carbon::parse($validated['transaction_date'])
            // ->utc()
            ->format('Y-m-d H:i:s');


        $transaction = Transaction::create($validated);

        // Return Inertia response for Inertia.js requests (from modal)
        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Transaction created successfully.');
        }

        // Return JSON response for other AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Transaction created successfully.',
                'transaction' => $transaction->load(['account.currency', 'transferToAccount.currency'])
            ]);
        }

        // Fallback redirect for non-AJAX requests
        return redirect()->route('transactions.index')
            ->with('success', 'Transaction created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        // Verify ownership through account relationship
        if ($transaction->account->user_id !== Auth::id()) {
            abort(403);
        }

        $transaction->load(['account.currency', 'transferToAccount.currency']);

        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        // Verify ownership through account relationship
        if ($transaction->account->user_id !== Auth::id()) {
            abort(403);
        }

        $accounts = Account::where('user_id', Auth::id())->with('currency')->get();
        $transaction->load(['account.currency', 'transferToAccount.currency']);

        return Inertia::render('Transactions/Edit', [
            'transaction' => $transaction,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        // Verify ownership through account relationship
        if ($transaction->account->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'transfer_to_account_id' => 'nullable|exists:accounts,id|different:account_id',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'converted_amount' => 'nullable|numeric|min:0.01',
            'exchange_rate_source' => 'nullable|string|in:manual,api',
        ]);

        // Verify new account ownership
        Account::where('id', $validated['account_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Verify transfer account ownership if applicable
        if ($validated['type'] === 'transfer' && $validated['transfer_to_account_id']) {
            Account::where('id', $validated['transfer_to_account_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();
            
            // Check if this is a cross-currency transfer
            $sourceAccount = Account::with('currency')->find($validated['account_id']);
            $destinationAccount = Account::with('currency')->find($validated['transfer_to_account_id']);
            
            if ($sourceAccount->currency_id !== $destinationAccount->currency_id) {
                // Cross-currency transfer requires exchange rate and converted amount
                if (!$validated['exchange_rate'] || !$validated['converted_amount']) {
                    return back()->withErrors([
                        'exchange_rate' => 'Exchange rate is required for cross-currency transfers.',
                        'converted_amount' => 'Converted amount is required for cross-currency transfers.'
                    ]);
                }
            }
        }

        // Parse ISO string from frontend (user's timezone) and convert to UTC for storage
        $validated['transaction_date'] = Carbon::parse($validated['transaction_date'])
            // ->utc()
            ->format('Y-m-d H:i:s');

        $transaction->update($validated);

        // Return Inertia response for Inertia.js requests (from modal)
        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Transaction updated successfully.');
        }

        // Return JSON response for other AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Transaction updated successfully.',
                'transaction' => $transaction->load(['account.currency', 'transferToAccount.currency'])
            ]);
        }

        // Fallback redirect for non-AJAX requests
        return redirect()->route('transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        // Verify ownership through account relationship
        if ($transaction->account->user_id !== Auth::id()) {
            abort(403);
        }

        $transaction->delete();

        // Return Inertia response for Inertia.js requests
        if (request()->header('X-Inertia')) {
            return back()->with('success', 'Transaction deleted successfully.');
        }

        // Return JSON response for other AJAX requests
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully.'
            ]);
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}
