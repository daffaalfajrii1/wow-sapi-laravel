<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            $request->session()->forget(['otp_user_id', 'otp_purpose']);

            return redirect()->route('login')->with('status', 'Email sudah diverifikasi. Silakan masuk.');
        }

        return view('auth.verify-otp', [
            'email' => $user->email,
        ]);
    }

    public function verify(Request $request, OtpService $otp): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi verifikasi tidak valid.']);
        }

        $otp->verify($user, $request->string('otp'), OtpService::PURPOSE_REGISTER, 'web');

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $request->session()->forget(['otp_user_id', 'otp_purpose']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route($user->homeRoute(), absolute: false));
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Email sudah diverifikasi. Silakan masuk.');
        }

        $otp->issue($user, OtpService::PURPOSE_REGISTER, 'web', $request->ip());

        return back()->with('status', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    protected function pendingUser(Request $request): ?User
    {
        if ($request->user() && ! $request->user()->hasVerifiedEmail()) {
            $request->session()->put('otp_user_id', $request->user()->id);
            $request->session()->put('otp_purpose', OtpService::PURPOSE_REGISTER);

            return $request->user();
        }

        $userId = $request->session()->get('otp_user_id');

        return $userId ? User::find($userId) : null;
    }
}
