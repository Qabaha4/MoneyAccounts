<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasscodeRequest;
use App\Models\Activity;
use App\Services\PasscodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PasscodeController extends Controller
{
    public function show(): Response
    {
        $user = request()->user();

        return Inertia::render('settings/Passcode', [
            'hasPasscode' => ! empty($user->passcode_hash),
            'hideDashboardBalance' => (bool) $user->hide_dashboard_balance,
        ]);
    }

    public function update(PasscodeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $changing = ! empty($user->passcode_hash);

        $user->update([
            'passcode_hash' => Hash::make($request->passcode),
        ]);

        Activity::log(
            $changing ? 'passcode_changed' : 'passcode_set',
            null,
            $user,
            $changing ? 'Changed app passcode' : 'Set app passcode',
            null,
            'passcode'
        );

        return back()->with('success', 'Passcode set successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->update([
            'passcode_hash' => null,
        ]);

        Activity::log('passcode_disabled', null, $user, 'Disabled app passcode', null, 'passcode');

        return back()->with('success', 'Passcode disabled successfully.');
    }

    public function toggleDashboardBalance(Request $request, PasscodeService $passcodeService): RedirectResponse
    {
        $user = $request->user();
        $currentlyHidden = (bool) $user->hide_dashboard_balance;
        $wantHidden = $request->boolean('hidden', !$currentlyHidden);

        if (!$wantHidden && $currentlyHidden && $passcodeService->hasPasscode()) {
            $request->validate([
                'passcode' => ['required', 'string'],
            ]);

            if (!$passcodeService->verify($request->passcode)) {
                return back()->withErrors(['passcode' => 'Invalid passcode.']);
            }

            $passcodeService->markVerified();
        }

        $user->update([
            'hide_dashboard_balance' => $wantHidden,
        ]);

        Activity::log(
            $wantHidden ? 'dashboard_balance_hidden' : 'dashboard_balance_shown',
            null,
            $user,
            $wantHidden ? 'Hidden dashboard balance' : 'Shown dashboard balance',
            null,
            'passcode'
        );

        return back();
    }
}
