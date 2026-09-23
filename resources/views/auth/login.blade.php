<x-guest-layout>
    <h1 class="text-2xl font-extrabold mb-1">Masuk</h1>
    <p class="text-sm text-muted mb-6">Masuk dengan email dan kata sandi. Tidak perlu kode OTP.</p>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full input-wow" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Kata sandi" />
                <a class="text-xs font-semibold text-primary" href="{{ route('password.request') }}">Lupa kata sandi?</a>
            </div>
            <div x-data="{ show: false }" class="relative mt-1">
                <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                    class="input-wow pr-12">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-muted hover:text-ink" :aria-label="show ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'">
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" name="remember" class="rounded border-line text-primary focus:ring-primary"> Ingat saya
        </label>
        <button class="btn-primary w-full">Masuk</button>
        <p class="text-sm text-center text-muted">Belum punya akun? <a class="text-primary font-semibold" href="{{ route('register') }}">Daftar</a></p>
    </form>
</x-guest-layout>
