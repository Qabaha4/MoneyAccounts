<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['account.currency', 'transferToAccount.currency'])
            ->whereHas('account', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->orderBy('transaction_date', 'desc');

        // Filter by account if specified
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Filter by type if specified
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate(20);
        $accounts = Account::where('user_id', Auth::id())->get();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'filters' => $request->only(['account_id', 'type']),
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
        }

        Transaction::create($validated);

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
        }

        $transaction->update($validated);

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

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}
