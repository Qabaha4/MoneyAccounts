<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Currency;
use App\Http\Requests\AccountRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = Account::with('currency')
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        $currencies = Currency::orderBy('code')->get();

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'currencies' => $currencies,
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
    public function show(Account $account)
    {
        // Verify ownership
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        // Load account with currency
        $account->load('currency');

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
            ->take(10)
            ->values();

        // Add a flag to distinguish incoming transfers for UI purposes
        $allTransactions = $allTransactions->map(function ($transaction) use ($account) {
            $transaction->is_incoming_transfer = $transaction->transfer_to_account_id == $account->id;
            return $transaction;
        });

        // Manually set the transactions relationship
        $account->setRelation('transactions', $allTransactions);

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

        $validatedData = $request->getValidatedDataForUpdate();



        $account->update($validatedData);

        return redirect()->back()
            ->with('success', 'Account updated successfully.');
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
