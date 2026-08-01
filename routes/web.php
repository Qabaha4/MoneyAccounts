<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityHistoryController;
use App\Http\Controllers\PasscodeController;
use App\Http\Controllers\TransactionController;
use App\Services\PasscodeService;

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
    $passcodeService = app(PasscodeService::class);

    // Fetch all accounts with needed data, including max transaction date for sorting
    $allAccounts = \App\Models\Account::with(['currency', 'transactions' => function ($query) {
        $query->orderBy('transaction_date', 'desc')->limit(5);
    }])
        ->where('user_id', $user->id)
        ->withMax('transactions', 'transaction_date')
        ->get();

    // Calculate totals using ALL accounts
    $totalBalance = $allAccounts->sum('balance');

    // Get all currencies used by user's accounts
    $userCurrencies = \App\Models\Currency::whereIn('id', $allAccounts->pluck('currency_id')->unique())
        ->where('is_active', true)
        ->orderBy('code')
        ->get();

    // Group account balances by currency
    $balancesByCurrency = [];

    foreach ($userCurrencies as $currency) {
        $balancesByCurrency[$currency->id] = $allAccounts
            ->where('currency_id', $currency->id)
            ->sum('balance');
    }

    // Sort accounts by last transaction date and take top 5 for display
    $recentAccounts = $allAccounts->sortByDesc('transactions_max_transaction_date')->values()->take(5);

    // Apply passcode masking
    $recentAccounts = $passcodeService->maskAccounts($recentAccounts);

    if ($passcodeService->hideDashboardTotal()) {
        $totalBalance = null;
        $balancesByCurrency = [];
    }

    // Get user's account IDs for filtering
    $userAccountIds = $allAccounts->pluck('id');

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
        'accounts' => $recentAccounts,
        'totalBalance' => $totalBalance,
        'recentTransactions' => $recentTransactions,
        'userCurrencies' => $userCurrencies,
        'balancesByCurrency' => $balancesByCurrency,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Authenticated routes
Route::middleware(['auth', 'verified', 'throttle:60,1'])->group(function () {
    Route::get('report', function () {
        $user = Auth::user();
        $accounts = \App\Models\Account::with('currency')
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        $totalBalance = $accounts->sum('balance');
        $activeCount = $accounts->where('is_active', true)->count();
        $currenciesCount = $accounts->pluck('currency_id')->unique()->count();

        return Inertia::render('Report/Index', [
            'accounts' => $accounts,
            'totalBalance' => number_format($totalBalance, 2, '.', ''),
            'activeCount' => $activeCount,
            'currenciesCount' => $currenciesCount,
        ]);
    })->name('report.index');

    Route::resource('accounts', AccountController::class)->except(['create', 'edit']);
    Route::post('accounts/{account}/toggle-hide-balance', [AccountController::class, 'toggleHideBalance'])->name('accounts.toggle-hide-balance');
    Route::get('accounts/{account}/report', [App\Http\Controllers\AccountReportController::class, 'show'])->name('accounts.report');
    Route::resource('transactions', TransactionController::class)->except(['create', 'edit', 'show']);

    // Global search endpoint
    Route::get('/search', [App\Http\Controllers\SearchController::class, 'search'])->name('search');

    // Activity history
    Route::get('/activity', [App\Http\Controllers\ActivityHistoryController::class, 'index'])->name('activity.index');

    // Passcode verification
    Route::post('/passcode/verify', [PasscodeController::class, 'verify'])
        ->middleware('throttle:5,1')
        ->name('passcode.verify');
});

// Admin backup download route (within Filament's middleware context)
Route::middleware(['web', 'auth', \App\Http\Middleware\AdminOnly::class])->prefix('admin')->group(function () {
    Route::get('/backups/{backup}/download', \App\Http\Controllers\Admin\BackupDownloadController::class)
        ->name('admin.backups.download');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
