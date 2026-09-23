<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request, OtpService $otp): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route($request->user()->homeRoute(), absolute: false));
        }

        $request->session()->put('otp_user_id', $request->user()->id);
        $request->session()->put('otp_purpose', OtpService::PURPOSE_REGISTER);

        if (! $otp->latestPending($request->user(), OtpService::PURPOSE_REGISTER, 'web')) {
            $otp->issue($request->user(), OtpService::PURPOSE_REGISTER, 'web', $request->ip());
        }

        return redirect()->route('otp.notice');
    }
}
