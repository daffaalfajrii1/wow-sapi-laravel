@extends('layouts.wow')
@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-extrabold">Master Vaksin</h1>
        <a class="text-sm text-primary" href="{{ route('admin.breeds.index') }}">Kelola ras →</a>
    </div>
</div>
<form method="POST" action="{{ route('admin.vaccines.store') }}" class="card p-4 mb-4 flex flex-wrap gap-2">
    @csrf
    <input name="name" class="input-wow" placeholder="Nama vaksin" required>
    <input name="default_interval_days" type="number" class="input-wow" placeholder="Interval hari">
    <button class="btn-primary">Tambah</button>
</form>
<div class="card overflow-x-auto">
<table class="min-w-full text-sm">
@foreach ($items as $v)
<tr class="border-t border-line">
<td class="p-3 font-semibold">{{ $v->name }}</td>
<td>{{ $v->default_interval_days }} hari</td>
<td>{{ $v->is_active ? 'Aktif' : 'Nonaktif' }}</td>
</tr>
@endforeach
</table>
<div class="p-3">{{ $items->links() }}</div>
</div>
@endsection
