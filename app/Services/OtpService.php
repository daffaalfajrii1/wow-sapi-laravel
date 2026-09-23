<?php

namespace App\Services;

use App\Models\LoginOtp;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const PURPOSE_REGISTER = 'register';

    public const PURPOSE_RESET = 'reset';

    public function issue(User $user, string $purpose, string $channel = 'web', ?string $ip = null): string
    {
        $this->assertPurpose($purpose);
        $this->assertResendAllowed($user, $purpose, $channel);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        LoginOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);

        LoginOtp::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'channel' => $channel,
            'purpose' => $purpose,
            'attempts' => 0,
            'max_attempts' => (int) config('wowsapi.otp.max_attempts', 5),
            'expires_at' => now()->addMinutes((int) config('wowsapi.otp.ttl_minutes', 5)),
            'ip_address' => $ip,
        ]);

        $this->sendMailAfterResponse($user, $purpose, $code);

        RateLimiter::hit(
            $this->resendKey($user, $purpose, $channel),
            (int) config('wowsapi.otp.resend_window_minutes', 10) * 60
        );

        return $code;
    }

    public function verify(User $user, string $code, string $purpose, string $channel = 'web'): LoginOtp
    {
        $this->assertPurpose($purpose);

        $otp = $this->latestPending($user, $purpose, $channel);

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak ditemukan. Silakan minta kode baru.',
            ]);
        }

        if ($otp->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.',
            ]);
        }

        if ($otp->isLocked()) {
            throw ValidationException::withMessages([
                'otp' => 'Terlalu banyak percobaan OTP. Silakan minta kode baru.',
            ]);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak sesuai.',
            ]);
        }

        $otp->update(['verified_at' => now()]);

        return $otp;
    }

    public function latestPending(User $user, string $purpose, string $channel = 'web'): ?LoginOtp
    {
        return LoginOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->whereNull('verified_at')
            ->latest()
            ->first();
    }

    protected function sendMailAfterResponse(User $user, string $purpose, string $code): void
    {
        $ttl = (int) config('wowsapi.otp.ttl_minutes', 5);
        $userId = $user->id;
        $resetUrl = null;

        if ($purpose === self::PURPOSE_RESET) {
            $resetUrl = url(route('password.reset', [
                'token' => Password::broker()->createToken($user),
                'email' => $user->email,
            ], false));
        }

        dispatch(function () use ($userId, $purpose, $code, $ttl, $resetUrl) {
            $user = User::query()->find($userId);
            if (! $user) {
                return;
            }

            try {
                if ($purpose === self::PURPOSE_RESET) {
                    $user->notify(new PasswordResetOtpNotification($code, $ttl, $resetUrl));
                } else {
                    $user->notify(new RegistrationOtpNotification($code, $ttl));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        })->afterResponse();
    }

    protected function assertPurpose(string $purpose): void
    {
        if (! in_array($purpose, [self::PURPOSE_REGISTER, self::PURPOSE_RESET], true)) {
            throw ValidationException::withMessages([
                'otp' => 'Jenis OTP tidak valid.',
            ]);
        }
    }

    protected function assertResendAllowed(User $user, string $purpose, string $channel): void
    {
        $key = $this->resendKey($user, $purpose, $channel);
        $max = (int) config('wowsapi.otp.resend_max_per_window', 3);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages([
                'otp' => 'Batas kirim ulang OTP tercapai. Coba lagi nanti.',
            ]);
        }

        $latest = $this->latestPending($user, $purpose, $channel);
        $wait = (int) config('wowsapi.otp.resend_seconds', 60);
        if ($latest && $latest->created_at->gt(now()->subSeconds($wait))) {
            throw ValidationException::withMessages([
                'otp' => 'Tunggu sebentar sebelum meminta kode OTP baru.',
            ]);
        }
    }

    protected function resendKey(User $user, string $purpose, string $channel): string
    {
        return 'otp-resend:'.$purpose.':'.$channel.':'.$user->id;
    }
}
