<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CattleService;
use App\Services\OtpService;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request, CattleService $cattleService, OtpService $otp)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['required', 'string', 'max:30'],
            'farm_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => null,
        ]);
        Role::findOrCreate('peternak');
        $user->assignRole('peternak');
        $cattleService->ensureFarmerProfile($user, [
            'phone' => $data['phone'],
            'farm_name' => $data['farm_name'] ?? $data['name'],
        ]);

        event(new Registered($user));
        $otp->issue($user, OtpService::PURPOSE_REGISTER, 'api', $request->ip());

        return ApiResponse::success(['email' => $user->email], 'Pendaftaran berhasil. Masukkan kode OTP dari email.', 201);
    }

    public function login(Request $request, OtpService $otp)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => 'Email tidak terdaftar.']);
        }
        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi salah.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Akun dinonaktifkan.']);
        }

        if (! $user->hasVerifiedEmail()) {
            $otp->issue($user, OtpService::PURPOSE_REGISTER, 'api', $request->ip());

            return ApiResponse::error('Email belum diverifikasi. Kode OTP telah dikirim ke email Anda.', 403);
        }

        if ($user->isAdmin()) {
            return ApiResponse::error('Admin hanya dapat masuk melalui dashboard web.', 403);
        }

        $token = $user->createToken('flutter')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('farmerProfile')),
        ], 'Masuk berhasil.');
    }

    public function verifyOtp(Request $request, OtpService $otp)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);
        $user = User::where('email', $data['email'])->firstOrFail();
        $otp->verify($user, $data['otp'], OtpService::PURPOSE_REGISTER, 'api');

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $token = $user->createToken('flutter')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('farmerProfile')),
        ], 'Email terverifikasi. Masuk berhasil.');
    }

    public function resendOtp(Request $request, OtpService $otp)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $data['email'])->firstOrFail();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::error('Email sudah diverifikasi. Silakan masuk dengan kata sandi.', 422);
        }

        $otp->issue($user, OtpService::PURPOSE_REGISTER, 'api', $request->ip());

        return ApiResponse::success(['email' => $user->email], 'Kode OTP baru dikirim.');
    }

    public function forgotPassword(Request $request, OtpService $otp)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $data['email'])->first();
        if ($user && $user->is_active) {
            $otp->issue($user, OtpService::PURPOSE_RESET, 'api', $request->ip());
        }

        return ApiResponse::success(
            ['email' => $data['email']],
            'Jika email terdaftar, kode OTP telah dikirim.'
        );
    }

    public function resetPassword(Request $request, OtpService $otp)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();
        $otp->verify($user, $data['otp'], OtpService::PURPOSE_RESET, 'api');

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        return ApiResponse::success(null, 'Kata sandi berhasil diperbarui. Silakan masuk.');
    }

    public function me(Request $request)
    {
        return ApiResponse::success(new UserResource($request->user()->load('farmerProfile')));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Keluar berhasil.');
    }

    public function profile(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'farm_name' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string'],
            'village' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'regency' => ['nullable', 'string', 'max:80'],
            'province' => ['nullable', 'string', 'max:80'],
        ]);
        $user = $request->user();
        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
        }
        $user->farmerProfile?->update(collect($data)->only([
            'phone', 'farm_name', 'address', 'village', 'district', 'regency', 'province',
        ])->all());

        return ApiResponse::success(new UserResource($user->fresh()->load('farmerProfile')), 'Profil diperbarui.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.confirmed' => 'Ulangi kata sandi baru tidak sama.',
        ]);
        $request->user()->update(['password' => Hash::make($data['password'])]);

        return ApiResponse::success(null, 'Kata sandi diperbarui.');
    }
}
