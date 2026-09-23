@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Pemeriksaan AI</h1>
@include('partials.ai-weight-photo-guide')
<p class="text-sm text-muted -mt-2 mb-4">Unggah foto bobot dari profil sapi (tab Pemeriksaan AI).</p>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Waktu</th><th>Sapi</th><th>Jenis</th><th>Status</th><th>Hasil</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($items as $e)
<tr>
    <td class="col-no">{{ table_no($items, $loop->index) }}</td>
    <td>{{ id_date($e->examined_at, true) }}</td>
    <td>@if($e->cattle)<x-cattle-code :cattle="$e->cattle" :panel="$panel" tab="ai" />@else — @endif</td>
    <td>{{ $e->typeLabel() }}</td>
    <td>{{ $e->statusLabel() }}</td>
    <td>{{ $e->estimated_weight_kg ? id_kg($e->estimated_weight_kg) : ($e->lumpy_label ?? '—') }}</td>
    <td>
        <form method="POST" action="{{ route($panel.'.cattle.ai.destroy', $e) }}" onsubmit="return confirm('Hapus indikasi/pemeriksaan AI ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold hover:underline">Hapus</button></form>
    </td>
</tr>
@empty
<tr><td class="px-4 py-8 text-muted" colspan="7">Belum ada pemeriksaan.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
