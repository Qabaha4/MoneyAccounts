<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $query = trim($request->input('query'));
        $isMysql = DB::connection()->getDriverName() === 'mysql';
        $ftQuery = $isMysql ? $this->buildFulltextQuery($query) : null;

        $accounts = Account::with('currency')
            ->where(function ($q) use ($query, $isMysql, $ftQuery) {
                if ($isMysql && $ftQuery) {
                    $q->whereRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', [$ftQuery]);
                }
                $q->orWhere('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->when($isMysql && $ftQuery, function ($q) use ($ftQuery) {
                return $q->orderByRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE) DESC', [$ftQuery]);
            }, function ($q) {
                return $q->orderBy('name');
            })
            ->limit(10)
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'description' => $account->description,
                    'balance' => (float) $account->balance,
                    'is_active' => $account->is_active,
                    'currency' => $account->currency->code,
                ];
            });

        $transactions = Transaction::with(['account.currency', 'transferToAccount'])
            ->where(function ($q) use ($query, $isMysql, $ftQuery) {
                if ($isMysql && $ftQuery) {
                    $q->whereRaw('MATCH(description, notes, category, reference_number) AGAINST(? IN BOOLEAN MODE)', [$ftQuery]);
                }
                $q->orWhere('description', 'LIKE', "%{$query}%")
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
                    'amount' => (float) $transaction->amount,
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

    /**
     * Build a MySQL FULLTEXT boolean-mode query with prefix matching
     * "checking account" → "+checking* +account*"
     */
    private function buildFulltextQuery(string $query): string
    {
        $words = preg_split('/[\s,]+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        $terms = [];

        foreach ($words as $word) {
            $word = preg_replace('/[+\-><()~*@"\'\\\\:;!?]/', '', $word);
            if (strlen($word) > 0) {
                $terms[] = '+' . $word . '*';
            }
        }

        return $terms ? implode(' ', $terms) : '+' . $query . '*';
    }
}
