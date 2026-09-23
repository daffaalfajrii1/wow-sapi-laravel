<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class ForgotPasswordOtpController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request, OtpService $otp): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower((string) $request->string('email'));
        $user = User::query()->where('email', $email)->first();

        if ($user && $user->is_active) {
            $otp->issue($user, OtpService::PURPOSE_RESET, 'web', $request->ip());
        }

        $request->session()->put('password_reset_email', $email);
        $request->session()->forget(['password_reset_user_id']);

        return redirect()->route('password.otp')->with(
            'status',
            'Jika email terdaftar, kode OTP telah dikirim. Periksa kotak masuk Anda.'
        );
    }

    public function otpForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot-password-otp', [
            'email' => $request->session()->get('password_reset_email'),
        ]);
    }

    public function verifyOtp(Request $request, OtpService $otp): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $email = $request->session()->get('password_reset_email');
        $user = $email ? User::query()->where('email', $email)->first() : null;
        if (! $user) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Sesi atur ulang tidak valid.',
            ]);
        }

        $otp->verify($user, $request->string('otp'), OtpService::PURPOSE_RESET, 'web');
        $request->session()->put('password_reset_user_id', $user->id);

        return redirect()->route('password.otp.reset')->with('status', 'Kode OTP benar. Silakan buat kata sandi baru.');
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');
        $user = $email ? User::query()->where('email', $email)->first() : null;
        if (! $user) {
            return redirect()->route('password.request');
        }

        $otp->issue($user, OtpService::PURPOSE_RESET, 'web', $request->ip());

        return back()->with('status', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    public function resetForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_user_id')) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot-password-reset');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::find($request->session()->get('password_reset_user_id'));
        if (! $user) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Sesi atur ulang tidak valid.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->string('password')),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        $request->session()->forget(['password_reset_email', 'password_reset_user_id']);

        return redirect()->route('login')->with('status', 'Kata sandi berhasil diperbarui. Silakan masuk.');
    }
}
