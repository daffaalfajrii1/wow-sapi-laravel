@extends('layouts.wow')
@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-extrabold">Master Ras Sapi</h1>
        <a class="text-sm text-primary" href="{{ route('admin.vaccines.index') }}">Kelola vaksin →</a>
    </div>
</div>
<form method="POST" action="{{ route('admin.breeds.store') }}" class="card p-4 mb-4 flex flex-wrap gap-2">
    @csrf
    <input name="name" class="input-wow" placeholder="Nama ras" required>
    <input name="code" class="input-wow" placeholder="Kode">
    <button class="btn-primary">Tambah</button>
</form>
<div class="card overflow-x-auto">
<table class="min-w-full text-sm">
@foreach ($items as $b)
<tr class="border-t border-line">
<td class="p-3 font-semibold">{{ $b->name }}</td>
<td>{{ $b->code }}</td>
<td>{{ $b->is_active ? 'Aktif' : 'Nonaktif' }}</td>
<td>
<form method="POST" action="{{ route('admin.breeds.update', $b) }}" class="flex gap-2">
@csrf @method('PUT')
<input type="hidden" name="name" value="{{ $b->name }}">
<input type="hidden" name="code" value="{{ $b->code }}">
<input type="hidden" name="is_active" value="{{ $b->is_active ? 0 : 1 }}">
<button class="text-xs text-primary">{{ $b->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
</form>
</td>
</tr>
@endforeach
</table>
<div class="p-3">{{ $items->links() }}</div>
</div>
@endsection
