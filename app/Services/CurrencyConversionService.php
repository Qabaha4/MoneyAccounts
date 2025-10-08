<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class CurrencyConversionService
{
    /**
     * Get the latest exchange rate between two currencies
     * This looks for the most recent transaction with an exchange rate between the currencies
     */
    public function getExchangeRate(Currency $fromCurrency, Currency $toCurrency): ?float
    {
        if ($fromCurrency->id === $toCurrency->id) {
            return 1.0;
        }

        // Look for recent transactions with exchange rates between these currencies
        $transaction = Transaction::whereNotNull('exchange_rate')
            ->whereNotNull('converted_amount')
            ->where(function ($query) use ($fromCurrency, $toCurrency) {
                $query->whereHas('account', function ($q) use ($fromCurrency) {
                    $q->where('currency_id', $fromCurrency->id);
                })->whereHas('transferToAccount', function ($q) use ($toCurrency) {
                    $q->where('currency_id', $toCurrency->id);
                });
            })
            ->orWhere(function ($query) use ($fromCurrency, $toCurrency) {
                $query->whereHas('account', function ($q) use ($toCurrency) {
                    $q->where('currency_id', $toCurrency->id);
                })->whereHas('transferToAccount', function ($q) use ($fromCurrency) {
                    $q->where('currency_id', $fromCurrency->id);
                });
            })
            ->orderBy('transaction_date', 'desc')
            ->first();

        if ($transaction) {
            // Calculate the rate based on the transaction direction
            $sourceAccount = $transaction->account;
            $destinationAccount = $transaction->transferToAccount;
            
            if ($sourceAccount->currency_id === $fromCurrency->id && 
                $destinationAccount->currency_id === $toCurrency->id) {
                return (float) $transaction->exchange_rate;
            } elseif ($sourceAccount->currency_id === $toCurrency->id && 
                      $destinationAccount->currency_id === $fromCurrency->id) {
                return 1.0 / (float) $transaction->exchange_rate;
            }
        }

        // Fallback to default rates if no transaction history exists
        return $this->getDefaultExchangeRate($fromCurrency, $toCurrency);
    }

    /**
     * Convert an amount from one currency to another
     */
    public function convertAmount(float $amount, Currency $fromCurrency, Currency $toCurrency): float
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        
        if ($rate === null) {
            return $amount; // Return original amount if conversion not possible
        }

        return $amount * $rate;
    }

    /**
     * Convert a collection of account balances to a target currency
     */
    public function convertAccountBalances(Collection $accounts, Currency $targetCurrency): float
    {
        $totalBalance = 0;

        foreach ($accounts as $account) {
            $convertedBalance = $this->convertAmount(
                (float) $account->balance,
                $account->currency,
                $targetCurrency
            );
            $totalBalance += $convertedBalance;
        }

        return $totalBalance;
    }

    /**
     * Get default exchange rates (fallback when no transaction history exists)
     * In a real application, this would fetch from an external API
     */
    private function getDefaultExchangeRate(Currency $fromCurrency, Currency $toCurrency): ?float
    {
        // Default exchange rates relative to USD
        $defaultRates = [
            'USD' => 1.0,
            'EUR' => 0.85,
            'GBP' => 0.73,
            'JPY' => 110.0,
            'CAD' => 1.25,
            'AUD' => 1.35,
            'SAR' => 3.75,
        ];

        $fromRate = $defaultRates[$fromCurrency->code] ?? null;
        $toRate = $defaultRates[$toCurrency->code] ?? null;

        if ($fromRate === null || $toRate === null) {
            return null;
        }

        // Convert through USD: from -> USD -> to
        return $toRate / $fromRate;
    }
}