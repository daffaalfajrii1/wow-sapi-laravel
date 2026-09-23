@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Profil</h1>
<div class="max-w-xl space-y-4">
<form method="POST" enctype="multipart/form-data" action="{{ route($panel.'.profile.save') }}" class="card p-6 space-y-3">
    @csrf @method('PUT')
    <h2 class="font-bold">Data profil</h2>
    <x-text-input name="name" class="w-full" :value="$user->name" />
    @if($user->farmerProfile)
        <x-text-input name="farm_name" class="w-full" :value="$user->farmerProfile->farm_name" placeholder="Nama peternakan" />
        <x-text-input name="phone" class="w-full" :value="$user->farmerProfile->phone" placeholder="Telepon" />
        <textarea name="address" class="input-wow" placeholder="Alamat">{{ $user->farmerProfile->address }}</textarea>
        <div class="grid grid-cols-2 gap-2">
            <input name="village" class="input-wow" placeholder="Desa" value="{{ $user->farmerProfile->village }}">
            <input name="district" class="input-wow" placeholder="Kecamatan" value="{{ $user->farmerProfile->district }}">
            <input name="regency" class="input-wow" placeholder="Kabupaten" value="{{ $user->farmerProfile->regency }}">
            <input name="province" class="input-wow" placeholder="Provinsi" value="{{ $user->farmerProfile->province }}">
        </div>
    @endif
    <input type="file" name="avatar" accept="image/*">
    <button class="btn-primary">Simpan profil</button>
</form>

<form method="POST" action="{{ route($panel.'.profile.password') }}" class="card p-6 space-y-3">
    @csrf @method('PUT')
    <h2 class="font-bold">Ganti kata sandi</h2>
    <p class="text-sm text-muted">Isi sandi saat ini, lalu sandi baru dua kali.</p>
    <x-password-input name="current_password" label="Kata sandi saat ini" autocomplete="current-password" />
    <x-password-input name="password" label="Kata sandi baru" autocomplete="new-password" />
    <x-password-input name="password_confirmation" label="Ulangi kata sandi baru" autocomplete="new-password" />
    <button class="btn-primary">Simpan kata sandi</button>
</form>
</div>
@endsection
