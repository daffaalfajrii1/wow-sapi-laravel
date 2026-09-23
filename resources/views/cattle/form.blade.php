@extends('layouts.wow')
@section('content')
@php $edit = $cattle->exists; @endphp
<h1 class="text-2xl font-extrabold mb-6">{{ $edit ? 'Ubah Sapi' : 'Tambah Sapi' }}</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $edit ? route($panel.'.cattle.update', $cattle) : route($panel.'.cattle.store') }}" class="card p-6 max-w-3xl space-y-4">
    @csrf
    @if($edit) @method('PUT') @endif
    @if(auth()->user()->isAdmin())
        <div>
            <x-input-label value="Peternak" />
            <select name="farmer_id" class="input-wow mt-1" required>
                @foreach ($farmers as $f)
                    <option value="{{ $f->id }}" @selected(old('farmer_id', $cattle->farmer_id)==$f->id)>{{ $f->user?->name }} — {{ $f->farm_name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <x-input-label value="Ras" />
            <select name="breed_id" class="input-wow mt-1" required>
                @foreach ($breeds as $b)
                    <option value="{{ $b->id }}" @selected(old('breed_id', $cattle->breed_id)==$b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Nama (opsional)" />
            <x-text-input name="name" class="mt-1 w-full" :value="old('name', $cattle->name)" />
        </div>
        <div>
            <x-input-label value="Jenis kelamin" />
            <select name="sex" class="input-wow mt-1">
                <option value="female" @selected(old('sex',$cattle->sex)==='female')>Betina</option>
                <option value="male" @selected(old('sex',$cattle->sex)==='male')>Jantan</option>
            </select>
        </div>
        <div>
            <x-input-label value="Tanggal lahir" />
            <x-text-input type="date" name="birth_date" class="mt-1 w-full" :value="old('birth_date', optional($cattle->birth_date)?->format('Y-m-d'))" />
            <label class="mt-1 flex items-center gap-2 text-xs text-muted"><input type="checkbox" name="estimated_birth_date" value="1" @checked($cattle->estimated_birth_date)> Perkiraan</label>
        </div>
        <div>
            <x-input-label value="Warna" />
            <x-text-input name="color" class="mt-1 w-full" :value="old('color', $cattle->color)" />
        </div>
        <div>
            <x-input-label value="Asal" />
            <x-text-input name="origin" class="mt-1 w-full" :value="old('origin', $cattle->origin)" />
        </div>
        <div>
            <x-input-label value="Tanggal masuk" />
            <x-text-input type="date" name="entry_date" class="mt-1 w-full" :value="old('entry_date', optional($cattle->entry_date)?->format('Y-m-d'))" />
        </div>
        <div>
            <x-input-label value="Foto utama" />
            <input type="file" name="main_photo" accept="image/jpeg,image/png" class="mt-1 text-sm">
        </div>
    </div>
    <div>
        <x-input-label value="Catatan" />
        <textarea name="notes" class="input-wow mt-1" rows="3">{{ old('notes', $cattle->notes) }}</textarea>
    </div>
    <div class="flex gap-2">
        <button class="btn-primary">Simpan</button>
        <a href="{{ route($panel.'.cattle.index') }}" class="btn-ghost">Batal</a>
    </div>
</form>
@endsection
