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
    $recentTransactions = \App\Models\Transaction::with(['account.currency', 'transferToAccount'])
        ->where('user_id', $user->id)
        ->orderBy('transaction_date', 'desc')
        ->limit(10)
        ->get();
    
    return Inertia::render('Dashboard', [
        'accounts' => $accounts,
        'totalBalance' => $totalBalance,
        'recentTransactions' => $recentTransactions,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('accounts', AccountController::class);
    Route::resource('transactions', TransactionController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
