<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Perform global search across accounts and transactions
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:255',
        ]);

        $query = $request->input('query');
        $user = Auth::user();

        // Search accounts by name (with tenant scoping automatically applied)
        $accounts = Account::with('currency')
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'description' => $account->description,
                    'balance' => $account->balance,
                    'is_active' => $account->is_active,
                    'currency' => $account->currency->code,
                ];
            });

        // Search transactions by description and notes (with tenant scoping automatically applied)
        $transactions = Transaction::with(['account.currency', 'transferToAccount'])
            ->where(function ($q) use ($query) {
                $q->where('description', 'LIKE', "%{$query}%")
                    ->orWhere('notes', 'LIKE', "%{$query}%")
                    ->orWhere('category', 'LIKE', "%{$query}%")
                    ->orWhere('reference_number', 'LIKE', "%{$query}%");
            })
            ->orderBy('transaction_date', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'account_id' => $transaction->account_id,
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'transaction_date' => $transaction->transaction_date,
                    'transfer_to_account' => $transaction->transferToAccount ? [
                        'id' => $transaction->transferToAccount->id,
                        'name' => $transaction->transferToAccount->name,
                    ] : null,
                    'currency' => $transaction->account->currency->code,
                    'account' => [
                        'name' => $transaction->account->name,
                    ],
                ];
            });

        return response()->json([
            'accounts' => $accounts,
            'transactions' => $transactions,
            'total_results' => $accounts->count() + $transactions->count(),
        ]);
    }
}
