<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasscodeService
{
    private ?bool $revealedCache = null;

    public function verify(string $passcode): bool
    {
        $user = Auth::user();

        if (!$user || !$user->passcode_hash) {
            return false;
        }

        return Hash::check($passcode, $user->passcode_hash);
    }

    public function markVerified(): void
    {
        session(['passcode_verified' => true]);
    }

    public function verified(): bool
    {
        return session('passcode_verified', false);
    }

    public function markBalanceReveal(): void
    {
        session(['balance_reveal' => true]);
    }

    public function isBalanceRevealed(): bool
    {
        if ($this->revealedCache !== null) {
            return $this->revealedCache;
        }

        if (session('balance_reveal')) {
            session()->forget('balance_reveal');
            $this->revealedCache = true;

            return true;
        }

        $this->revealedCache = false;

        return false;
    }

    public function hasPasscode(): bool
    {
        $user = Auth::user();

        return $user && !empty($user->passcode_hash);
    }

    public function hideAccountBalance(Account $account): bool
    {
        if ($this->isBalanceRevealed()) {
            return false;
        }

        return $account->hide_balance;
    }

    public function dashboardHidden(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($this->isBalanceRevealed()) {
            return false;
        }

        return (bool) $user->hide_dashboard_balance;
    }

    public function hideDashboardTotal(): bool
    {
        return $this->dashboardHidden();
    }

    public function maskAccounts(Collection $accounts): Collection
    {
        $dashboardHidden = $this->dashboardHidden();

        return $accounts->map(function (Account $account) use ($dashboardHidden) {
            if ($dashboardHidden || $this->hideAccountBalance($account)) {
                $account->balance = null;
                $account->balance_hidden = true;
            }

            return $account;
        });
    }
}
