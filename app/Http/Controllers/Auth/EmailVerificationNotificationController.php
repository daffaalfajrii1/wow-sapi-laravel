<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route($request->user()->homeRoute(), absolute: false));
        }

        $otp->issue($request->user(), OtpService::PURPOSE_REGISTER, 'web', $request->ip());
        $request->session()->put('otp_user_id', $request->user()->id);
        $request->session()->put('otp_purpose', OtpService::PURPOSE_REGISTER);

        return redirect()->route('otp.notice')->with('status', 'Kode OTP baru telah dikirim ke email Anda.');
    }
}
