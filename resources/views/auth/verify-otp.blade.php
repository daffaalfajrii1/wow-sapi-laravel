<x-guest-layout>
    <h1 class="text-2xl font-extrabold mb-1">Verifikasi email</h1>
    <p class="text-sm text-muted mb-6">Masukkan kode OTP 6 digit yang kami kirim ke <strong class="text-ink">{{ $email }}</strong>. Berlaku 5 menit. Setelah terverifikasi, Anda masuk tanpa OTP.</p>
    @if (session('status'))
        <p class="mb-4 text-sm text-primary-dark bg-primary-soft rounded-xl px-3 py-2">{{ session('status') }}</p>
    @endif
    <form method="POST" action="{{ route('otp.verify') }}" class="space-y-4">
        @csrf
        <input name="otp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" class="input-wow tracking-[0.6em] text-center text-2xl font-bold" placeholder="••••••" required>
        <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        <button class="btn-primary w-full">Verifikasi</button>
    </form>
    <form method="POST" action="{{ route('otp.resend') }}" class="mt-4 text-center">
        @csrf
        <button class="text-sm text-primary font-semibold">Kirim ulang kode</button>
    </form>
    <p class="mt-6 text-sm text-center text-muted">Salah akun? <a class="text-primary font-semibold" href="{{ route('login') }}">Kembali masuk</a></p>
</x-guest-layout>
