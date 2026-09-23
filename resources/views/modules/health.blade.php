@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Kesehatan</h1>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Tanggal</th><th>Sapi</th><th>Judul</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($items as $e)
    <tr>
    <td class="col-no">{{ table_no($items, $loop->index) }}</td>
    <td>{{ id_date($e->occurred_at) }}</td>
    <td>@if($e->cattle)<x-cattle-code :cattle="$e->cattle" :panel="$panel" tab="kesehatan" />@else — @endif</td>
    <td>{{ $e->title }}</td>
    <td>{{ $e->status }}</td>
    <td>
        <div class="flex gap-3">
            @if($e->cattle)
                <a href="{{ route($panel.'.cattle.show', [$e->cattle, 'tab' => 'kesehatan', 'health' => $e->id]) }}" class="text-primary text-xs font-semibold hover:underline">Ubah</a>
            @endif
            <form method="POST" action="{{ route($panel.'.cattle.health.destroy', $e) }}" onsubmit="return confirm('Hapus catatan kesehatan ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold hover:underline">Hapus</button></form>
        </div>
    </td>
</tr>
@empty
<tr><td class="px-4 py-8 text-muted" colspan="6">Belum ada catatan.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
