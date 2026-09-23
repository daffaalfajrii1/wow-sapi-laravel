@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Vaksinasi</h1>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead>
<tr>
    <th class="col-no">No.</th>
    <th>Tanggal</th>
    <th>Sapi</th>
    <th>Vaksin</th>
    <th>Status</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>
@forelse ($items as $e)
<tr>
    <td class="col-no">{{ table_no($items, $loop->index) }}</td>
    <td>{{ id_date($e->scheduled_date) }}</td>
    <td>
        @if($e->cattle)
            <x-cattle-code :cattle="$e->cattle" :panel="$panel" tab="vaksin" />
        @else — @endif
    </td>
    <td>{{ $e->vaccine?->name }}</td>
    <td>
        @php $st = $e->status; @endphp
        <span class="{{ $st==='done' ? 'badge-sehat' : ($st==='scheduled' ? 'badge-suspek' : 'badge-bahaya') }}">{{ $e->statusLabel() }}</span>
    </td>
    <td>
        <div class="flex flex-wrap items-center gap-3">
            @if($e->status==='scheduled')
                <form method="POST" action="{{ route($panel.'.vaccinations.complete', $e) }}">@csrf<button class="text-primary text-xs font-semibold hover:underline" onclick="event.stopPropagation()">Selesai</button></form>
            @endif
            @if($e->cattle)
                <a href="{{ route($panel.'.cattle.show', [$e->cattle, 'tab' => 'vaksin', 'schedule' => $e->id]) }}" class="text-primary text-xs font-semibold hover:underline">Ubah</a>
            @endif
            <form method="POST" action="{{ route($panel.'.cattle.schedules.destroy', $e) }}" onsubmit="return confirm('Hapus jadwal vaksin ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold hover:underline">Hapus</button></form>
        </div>
    </td>
</tr>
@empty
<tr><td class="px-4 py-8 text-muted" colspan="6">Belum ada jadwal.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
