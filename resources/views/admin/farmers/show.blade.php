@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-2">{{ $farmer->user?->name }}</h1>
<p class="text-muted mb-4">{{ $farmer->farm_name }} · {{ $farmer->phone }} · {{ $farmer->regency }} {{ $farmer->province }}</p>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Kode</th><th>Ras</th><th>Status</th></tr></thead>
<tbody>
@forelse ($farmer->cattle as $c)
<tr class="cursor-pointer" onclick="window.location='{{ route('admin.cattle.show', $c) }}'">
    <td class="col-no">{{ $loop->iteration }}</td>
    <td><a class="text-primary font-semibold hover:underline" href="{{ route('admin.cattle.show', $c) }}">{{ $c->code }}</a></td>
    <td>{{ $c->breed?->name }}</td>
    <td>{{ $c->statusLabel() }}</td>
</tr>
@empty
<tr><td colspan="4" class="px-4 py-8 text-muted">Belum ada sapi.</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
@endsection
