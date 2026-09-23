@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Reproduksi</h1>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Tanggal</th><th>Sapi</th><th>Kejadian</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($items as $e)
    <tr>
    <td class="col-no">{{ table_no($items, $loop->index) }}</td>
    <td>{{ id_date($e->event_date) }}</td>
    <td>@if($e->cattle)<x-cattle-code :cattle="$e->cattle" :panel="$panel" tab="reproduksi" />@else — @endif</td>
    <td>{{ $e->typeLabel() }}</td>
    <td>
        <div class="flex gap-3">
            @if($e->cattle)
                <a href="{{ route($panel.'.cattle.show', [$e->cattle, 'tab' => 'reproduksi', 'repro' => $e->id]) }}" class="text-primary text-xs font-semibold hover:underline">Ubah</a>
            @endif
            <form method="POST" action="{{ route($panel.'.cattle.reproduction.destroy', $e) }}" onsubmit="return confirm('Hapus catatan reproduksi ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold hover:underline">Hapus</button></form>
        </div>
    </td>
</tr>
@empty
<tr><td class="px-4 py-8 text-muted" colspan="5">Belum ada data reproduksi.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
