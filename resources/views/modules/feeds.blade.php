@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Pakan</h1>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Tanggal</th><th>Sapi</th><th>Pakan</th><th>Jumlah</th><th>Biaya</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($items as $e)
    <tr>
    <td class="col-no">{{ table_no($items, $loop->index) }}</td>
    <td>{{ id_date($e->fed_at) }}</td>
    <td>@if($e->cattle)<x-cattle-code :cattle="$e->cattle" :panel="$panel" tab="pakan" />@else — @endif</td>
    <td>{{ $e->feed_name }}</td>
    <td>{{ $e->quantity }} {{ $e->unit }}</td>
    <td>Rp {{ number_format($e->cost) }}</td>
    <td>
        <div class="flex gap-3">
            @if($e->cattle)
                <a href="{{ route($panel.'.cattle.show', [$e->cattle, 'tab' => 'pakan', 'feed' => $e->id]) }}" class="text-primary text-xs font-semibold hover:underline">Ubah</a>
            @endif
            <form method="POST" action="{{ route($panel.'.cattle.feeds.destroy', $e) }}" onsubmit="return confirm('Hapus catatan pakan ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold hover:underline">Hapus</button></form>
        </div>
    </td>
</tr>
@empty
<tr><td class="px-4 py-8 text-muted" colspan="7">Belum ada data pakan.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
