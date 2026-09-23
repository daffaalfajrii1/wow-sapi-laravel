<x-guest-layout>
    <h1 class="text-2xl font-extrabold mb-1">Daftar Peternak</h1>
    <p class="text-sm text-muted mb-6">Buat akun, lalu verifikasi email dengan kode OTP 6 digit (sekali saja).</p>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label value="Nama" />
            <x-text-input name="name" class="mt-1 w-full" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="Nama peternakan" />
            <x-text-input name="farm_name" class="mt-1 w-full" :value="old('farm_name')" />
        </div>
        <div>
            <x-input-label value="Telepon" />
            <x-text-input name="phone" class="mt-1 w-full" :value="old('phone')" required />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="Email" />
            <x-text-input type="email" name="email" class="mt-1 w-full" :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="Kata sandi" />
            <x-text-input type="password" name="password" class="mt-1 w-full" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="Konfirmasi kata sandi" />
            <x-text-input type="password" name="password_confirmation" class="mt-1 w-full" required />
        </div>
        <button class="btn-primary w-full">Daftar</button>
        <p class="text-sm text-center text-muted">Sudah punya akun? <a class="text-primary font-semibold" href="{{ route('login') }}">Masuk</a></p>
    </form>
</x-guest-layout>
