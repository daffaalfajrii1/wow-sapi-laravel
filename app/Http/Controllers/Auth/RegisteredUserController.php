<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CattleService;
use App\Services\OtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, CattleService $cattleService, OtpService $otp): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['required', 'string', 'max:30'],
            'farm_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => null,
        ]);

        Role::findOrCreate('peternak');
        $user->assignRole('peternak');
        $cattleService->ensureFarmerProfile($user, [
            'phone' => $request->string('phone'),
            'farm_name' => $request->string('farm_name') ?: $user->name,
        ]);

        event(new Registered($user));

        $request->session()->put('otp_user_id', $user->id);
        $request->session()->put('otp_purpose', OtpService::PURPOSE_REGISTER);

        try {
            $otp->issue($user, OtpService::PURPOSE_REGISTER, 'web', $request->ip());
            $status = 'Pendaftaran berhasil. Masukkan kode OTP yang kami kirim ke email Anda.';
        } catch (\Throwable $e) {
            report($e);
            $status = 'Akun sudah dibuat. Jika kode OTP belum sampai, tekan kirim ulang atau periksa folder spam.';
        }

        return redirect()->route('otp.notice')->with('status', $status);
    }
}
