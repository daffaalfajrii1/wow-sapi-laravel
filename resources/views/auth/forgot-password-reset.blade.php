<x-guest-layout>
    <h1 class="text-2xl font-extrabold mb-1">Kata sandi baru</h1>
    <p class="text-sm text-muted mb-6">Buat kata sandi baru untuk akun WOW SAPI Anda.</p>
    @if (session('status'))
        <p class="mb-4 text-sm text-primary-dark bg-primary-soft rounded-xl px-3 py-2">{{ session('status') }}</p>
    @endif
    <form method="POST" action="{{ route('password.otp.update') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="password" value="Kata sandi baru" />
            <x-text-input id="password" class="block mt-1 w-full input-wow" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi kata sandi" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full input-wow" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>
        <button class="btn-primary w-full">Simpan kata sandi</button>
    </form>
</x-guest-layout>
