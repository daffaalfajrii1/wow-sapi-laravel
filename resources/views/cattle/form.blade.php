@extends('layouts.wow')
@section('content')
@php $edit = $cattle->exists; @endphp
<h1 class="text-xl sm:text-2xl font-extrabold mb-4 sm:mb-6">{{ $edit ? 'Ubah Sapi' : 'Tambah Sapi' }}</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $edit ? route($panel.'.cattle.update', $cattle) : route($panel.'.cattle.store') }}" class="card p-4 sm:p-6 max-w-3xl space-y-5">
    @csrf
    @if($edit) @method('PUT') @endif
    @if(auth()->user()->isAdmin())
        <div class="field-wow">
            <x-input-label value="Peternak" />
            <select name="farmer_id" class="input-wow" required>
                @foreach ($farmers as $f)
                    <option value="{{ $f->id }}" @selected(old('farmer_id', $cattle->farmer_id)==$f->id)>{{ $f->user?->name }} — {{ $f->farm_name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="field-wow">
            <x-input-label value="Ras" />
            <select name="breed_id" class="input-wow" required>
                @foreach ($breeds as $b)
                    <option value="{{ $b->id }}" @selected(old('breed_id', $cattle->breed_id)==$b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field-wow">
            <x-input-label value="Nama (opsional)" />
            <x-text-input name="name" class="w-full" :value="old('name', $cattle->name)" />
        </div>
        <div class="field-wow">
            <x-input-label value="Jenis kelamin" />
            <select name="sex" class="input-wow">
                <option value="female" @selected(old('sex',$cattle->sex)==='female')>Betina</option>
                <option value="male" @selected(old('sex',$cattle->sex)==='male')>Jantan</option>
            </select>
        </div>
        <div class="field-wow">
            <x-input-label value="Tanggal lahir" />
            <x-text-input type="date" name="birth_date" class="w-full" :value="old('birth_date', optional($cattle->birth_date)?->format('Y-m-d'))" />
            <label class="flex items-center gap-2 text-sm text-muted pt-1">
                <input type="checkbox" name="estimated_birth_date" value="1" class="rounded border-line text-primary focus:ring-primary" @checked($cattle->estimated_birth_date)>
                Perkiraan
            </label>
        </div>
        <div class="field-wow">
            <x-input-label value="Warna" />
            <x-text-input name="color" class="w-full" :value="old('color', $cattle->color)" />
        </div>
        <div class="field-wow">
            <x-input-label value="Asal" />
            <x-text-input name="origin" class="w-full" :value="old('origin', $cattle->origin)" />
        </div>
        <div class="field-wow">
            <x-input-label value="Tanggal masuk" />
            <x-text-input type="date" name="entry_date" class="w-full" :value="old('entry_date', optional($cattle->entry_date)?->format('Y-m-d'))" />
        </div>
        <div class="field-wow">
            <x-input-label value="Foto utama" />
            <x-file-input name="main_photo" />
        </div>
    </div>
    <div class="field-wow">
        <x-input-label value="Catatan" />
        <textarea name="notes" class="input-wow" rows="3">{{ old('notes', $cattle->notes) }}</textarea>
    </div>
    <div class="flex flex-col-reverse sm:flex-row gap-2 pt-1">
        <a href="{{ route($panel.'.cattle.index') }}" class="btn-ghost w-full sm:w-auto">Batal</a>
        <button class="btn-primary w-full sm:w-auto">Simpan</button>
    </div>
</form>
@endsection
