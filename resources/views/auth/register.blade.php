<x-guest-layout>
    <h1 class="text-xl sm:text-2xl font-extrabold mb-1">Daftar Peternak</h1>
    <p class="text-sm text-muted mb-5 leading-relaxed">Buat akun, lalu verifikasi email dengan kode OTP 6 digit (sekali saja).</p>
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <div class="field-wow">
            <x-input-label value="Nama" />
            <x-text-input name="name" class="w-full" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="field-wow">
            <x-input-label value="Nama peternakan" />
            <x-text-input name="farm_name" class="w-full" :value="old('farm_name')" />
        </div>
        <div class="field-wow">
            <x-input-label value="Telepon" />
            <x-text-input name="phone" inputmode="tel" class="w-full" :value="old('phone')" required />
            <x-input-error :messages="$errors->get('phone')" />
        </div>
        <div class="field-wow">
            <x-input-label value="Email" />
            <x-text-input type="email" name="email" class="w-full" :value="old('email')" required autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <x-password-input name="password" label="Kata sandi" autocomplete="new-password" required />
        <x-password-input name="password_confirmation" label="Konfirmasi kata sandi" autocomplete="new-password" required />
        <button class="btn-primary w-full">Daftar</button>
        <p class="text-sm text-center text-muted leading-relaxed">Sudah punya akun? <a class="text-primary font-semibold" href="{{ route('login') }}">Masuk</a></p>
    </form>
</x-guest-layout>
