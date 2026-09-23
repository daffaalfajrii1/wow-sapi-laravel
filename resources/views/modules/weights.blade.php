@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Perkembangan Bobot</h1>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Sapi</th><th>Peternak</th><th>Bobot terakhir</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($items as $row)
<tr class="cursor-pointer" onclick="window.location='{{ route($panel.'.cattle.show', [$row, 'tab'=>'bobot']) }}'">
    <td class="col-no">{{ table_no($items, $loop->index) }}</td>
    <td><x-cattle-code :cattle="$row" :panel="$panel" tab="bobot" /></td>
    <td>{{ $row->farmer?->user?->name }}</td>
    <td>{{ $row->latestWeight ? id_kg($row->latestWeight->weight_kg) : '—' }}</td>
    <td><a class="text-primary text-sm font-semibold hover:underline" href="{{ route($panel.'.cattle.show', [$row, 'tab'=>'bobot']) }}">Grafik</a></td>
</tr>
@empty
<tr><td class="px-4 py-8 text-muted" colspan="5">Belum ada data.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
