<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, OtpService $otp): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user) {
            $request->authenticateFailed('email', 'Email tidak terdaftar.');
        }

        if (! Hash::check($request->string('password'), $user->password)) {
            $request->authenticateFailed('password', 'Kata sandi salah.');
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Akun dinonaktifkan.']);
        }

        RateLimiter::clear($request->throttleKey());

        if (! $user->hasVerifiedEmail()) {
            $otp->issue($user, OtpService::PURPOSE_REGISTER, 'web', $request->ip());
            $request->session()->put('otp_user_id', $user->id);
            $request->session()->put('otp_purpose', OtpService::PURPOSE_REGISTER);

            return redirect()->route('otp.notice')->with(
                'status',
                'Email belum diverifikasi. Kode OTP telah dikirim ke email Anda.'
            );
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route($user->homeRoute(), absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
