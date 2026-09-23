@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">BCS</h1>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Tanggal</th><th>Sapi</th><th>BCS</th><th>Kategori</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($items as $e)
<tr>
    <td class="col-no">{{ table_no($items, $loop->index) }}</td>
    <td>{{ id_date($e->assessed_at) }}</td>
    <td>@if($e->cattle)<x-cattle-code :cattle="$e->cattle" :panel="$panel" tab="bcs" />@else — @endif</td>
    <td>{{ number_format($e->score, 1) }}</td>
    <td>{{ $e->category }}</td>
    <td>
        @if($e->cattle)
            <a class="text-primary font-semibold hover:underline" href="{{ route($panel.'.cattle.show', [$e->cattle, 'tab' => 'bcs', 'record' => $e->id]) }}">Detail</a>
        @endif
    </td>
</tr>
@empty
<tr><td class="px-4 py-8 text-muted" colspan="6">Belum ada BCS.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
