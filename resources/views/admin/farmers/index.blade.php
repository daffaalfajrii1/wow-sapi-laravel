@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Peternak</h1>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Nama</th><th>Peternakan</th><th>Telepon</th><th>Sapi</th><th>Akun</th></tr></thead>
<tbody>
@forelse ($farmers as $f)
<tr>
    <td class="col-no">{{ table_no($farmers, $loop->index) }}</td>
    <td><a class="font-semibold text-primary hover:underline" href="{{ route('admin.farmers.show', $f) }}">{{ $f->user?->name }}</a></td>
    <td>{{ $f->farm_name }}</td>
    <td>{{ $f->phone }}</td>
    <td>{{ $f->cattle_count }}</td>
    <td>
        @if ($f->user?->hasVerifiedEmail())
            <span class="badge-sehat">Terverifikasi</span>
        @else
            <span class="badge-suspek">Menunggu OTP</span>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="6" class="px-4 py-8 text-muted">Belum ada peternak.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $farmers->links() }}</div>
</div>
@endsection
