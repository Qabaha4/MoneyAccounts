<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Currency;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

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

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency_id' => 'required|exists:currencies,id',
            'type' => 'required|in:checking,savings,credit,investment,cash,other',
            'initial_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['balance'] = $validated['initial_balance'] ?? 0;

        Account::create($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Account created successfully.');
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

        $account->load(['currency', 'transactions' => function ($query) {
            $query->with('transferToAccount')
                  ->orderBy('transaction_date', 'desc')
                  ->limit(10);
        }]);

        return Inertia::render('Accounts/Show', [
            'account' => $account,
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
    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency_id' => 'required|exists:currencies,id',
            'type' => 'required|in:checking,savings,credit,investment,cash,other',
            'is_active' => 'boolean',
        ]);

        $account->update($validated);

        return redirect()->route('accounts.index')
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
