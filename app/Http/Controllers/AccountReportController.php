<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Transaction;
use App\Services\PasscodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class AccountReportController extends Controller
{
    public function show(Request $request, Account $account)
    {
        $passcodeService = app(PasscodeService::class);

        // Verify ownership
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        // Load account with currency
        $account->load('currency');

        // Check if account is locked
        if ($account->is_locked && $passcodeService->hasPasscode() && !$passcodeService->verified()) {
            return Inertia::render('Accounts/Report', [
                'account' => $account,
                'transactions' => collect(),
                'statistics' => [],
                'transactionsByType' => [],
                'startDate' => now()->subMonths(1)->toDateString(),
                'endDate' => now()->toDateString(),
                'generatedAt' => Carbon::now()->toDateTimeString(),
                'user' => Auth::user(),
                'locked' => true,
            ]);
        }

        // Apply balance masking
        if ($passcodeService->hideAccountBalance($account)) {
            $account->balance = null;
            $account->balance_hidden = true;
        }

        // Get date range from request or use defaults
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->subMonths(1)->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Get all transactions for this account within the date range
        $outgoingTransactions = $account->transactions()
            ->with(['account.currency', 'transferToAccount.currency'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $incomingTransfers = Transaction::where('transfer_to_account_id', $account->id)
            ->where('user_id', Auth::id())
            ->with(['account.currency', 'transferToAccount.currency'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        // Combine and sort all transactions
        $allTransactions = $outgoingTransactions->concat($incomingTransfers)
            ->sortBy('transaction_date')
            ->values();

        // Add a flag to distinguish incoming transfers
        $allTransactions = $allTransactions->map(function ($transaction) use ($account) {
            $transaction->is_incoming_transfer = $transaction->transfer_to_account_id == $account->id;
            return $transaction;
        });

        // Calculate statistics
        $totalIncome = $allTransactions->where('type', 'income')->sum('amount');
        $totalExpense = $allTransactions->where('type', 'expense')->sum('amount');

        $transfersOut = $allTransactions->where('type', 'transfer')
            ->where('is_incoming_transfer', false)
            ->sum('amount');

        $transfersIn = $allTransactions->where('type', 'transfer')
            ->where('is_incoming_transfer', true)
            ->sum(function ($transaction) {
                return $transaction->converted_amount ?? $transaction->amount;
            });

        $netChange = $totalIncome - $totalExpense - $transfersOut + $transfersIn;

        // Calculate opening balance (balance at start of period)
        $currentBalance = $account->balance;
        $openingBalance = $currentBalance - $netChange;

        $statistics = [
            'opening_balance' => $openingBalance,
            'closing_balance' => $currentBalance,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'transfers_in' => $transfersIn,
            'transfers_out' => $transfersOut,
            'net_change' => $netChange,
            'transaction_count' => $allTransactions->count(),
        ];

        // Group transactions by type for summary
        $transactionsByType = [
            'income' => $allTransactions->where('type', 'income')->count(),
            'expense' => $allTransactions->where('type', 'expense')->count(),
            'transfer' => $allTransactions->where('type', 'transfer')->count(),
        ];

        if ($request->has('export')) {
            Activity::log('exported', $account, Auth::user(), "Exported report for account '{$account->name}'");
        }

        if ($request->has('print')) {
            Activity::log('printed', $account, Auth::user(), "Printed report for account '{$account->name}'");
        }

        return Inertia::render('Accounts/Report', [
            'account' => $account,
            'transactions' => $allTransactions,
            'statistics' => $statistics,
            'transactionsByType' => $transactionsByType,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'generatedAt' => Carbon::now()->toDateTimeString(),
            'user' => Auth::user(),
        ]);
    }
}
