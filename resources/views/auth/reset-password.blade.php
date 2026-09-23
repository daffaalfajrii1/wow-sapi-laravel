<x-guest-layout>
    <h1 class="text-2xl font-extrabold mb-1">Atur ulang kata sandi</h1>
    <p class="text-sm text-muted mb-6">Masukkan kata sandi baru untuk akun Anda.</p>
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full input-wow" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
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
