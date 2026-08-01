<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\PasscodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasscodeController extends Controller
{
    public function verify(Request $request, PasscodeService $passcodeService): RedirectResponse
    {
        $request->validate([
            'passcode' => ['required', 'string'],
        ]);

        if ($passcodeService->verify($request->passcode)) {
            $passcodeService->markVerified();
            $passcodeService->markBalanceReveal();

            Activity::log('passcode_verified', null, $request->user(), 'Verified app passcode', null, 'passcode');

            return back();
        }

        if ($passcodeService->hasPasscode()) {
            Activity::log('passcode_failed', null, $request->user(), 'Failed passcode verification attempt', null, 'passcode');
        }

        return back()->withErrors(['passcode' => 'Invalid passcode.']);
    }
}
