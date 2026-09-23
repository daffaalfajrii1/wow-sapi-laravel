<x-guest-layout>
    <h1 class="text-2xl font-extrabold mb-1">Lupa kata sandi</h1>
    <p class="text-sm text-muted mb-6">Masukkan email akun. Kami akan mengirim kode OTP 6 digit untuk mengatur ulang kata sandi.</p>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full input-wow" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <button class="btn-primary w-full">Kirim kode OTP</button>
        <p class="text-sm text-center text-muted"><a class="text-primary font-semibold" href="{{ route('login') }}">Kembali masuk</a></p>
    </form>
</x-guest-layout>
