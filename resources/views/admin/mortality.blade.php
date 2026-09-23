@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Kematian</h1>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Tanggal</th><th>Sapi</th><th>Peternak</th><th>Penyebab</th></tr></thead>
<tbody>
@forelse ($items as $e)
<tr>
    <td class="col-no">{{ table_no($items, $loop->index) }}</td>
    <td>{{ id_date($e->died_at) }}</td>
    <td>@if($e->cattle)<x-cattle-code :cattle="$e->cattle" panel="admin" />@else — @endif</td>
    <td>{{ $e->cattle?->farmer?->user?->name }}</td>
    <td>{{ $e->confirmed_cause ?: $e->suspected_cause }}</td>
</tr>
@empty
<tr><td class="px-4 py-8 text-muted" colspan="5">Belum ada data kematian.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
