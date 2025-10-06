<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/navigation-test', function () {
    return Inertia::render('NavigationTest');
})->name('navigation.test');

Route::post('/locale', function () {
    $locale = request('locale');
    $supportedLocales = ['en', 'ar'];
    
    if (in_array($locale, $supportedLocales)) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    
    return back();
})->name('locale.switch');

Route::get('dashboard', function () {
    $user = Auth::user();
    $accounts = \App\Models\Account::with(['currency', 'transactions' => function ($query) {
        $query->orderBy('transaction_date', 'desc')->limit(5);
    }])->where('user_id', $user->id)->get();
    
    $totalBalance = $accounts->sum('balance');
    
    // Get user's account IDs for filtering
    $userAccountIds = $accounts->pluck('id');
    
    // Get all transactions where either the from_account or to_account belongs to the user
    $recentTransactions = \App\Models\Transaction::with(['account.currency', 'transferToAccount.currency'])
        ->where(function ($query) use ($user, $userAccountIds) {
            $query->where('user_id', $user->id) // Outgoing transactions
                  ->orWhereIn('transfer_to_account_id', $userAccountIds); // Incoming transfers
        })
        ->orderBy('transaction_date', 'desc')
        ->limit(10)
        ->get();

    // Add a flag to distinguish incoming transfers for UI purposes
    $recentTransactions = $recentTransactions->map(function ($transaction) use ($userAccountIds) {
        $transaction->is_incoming_transfer = $transaction->transfer_to_account_id && 
            in_array($transaction->transfer_to_account_id, $userAccountIds->toArray()) &&
            !in_array($transaction->account_id, $userAccountIds->toArray());
        return $transaction;
    });
    
    return Inertia::render('Dashboard', [
        'accounts' => $accounts,
        'totalBalance' => $totalBalance,
        'recentTransactions' => $recentTransactions,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('accounts', AccountController::class)->except(['create', 'edit']);
    Route::resource('transactions', TransactionController::class)->except(['create', 'edit', 'show']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
