@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-2">{{ $farmer->user?->name }}</h1>
<p class="text-muted mb-4">{{ $farmer->farm_name }} · {{ $farmer->phone }} · {{ $farmer->regency }} {{ $farmer->province }}</p>
@if ($farmer->user && ! $farmer->user->hasVerifiedEmail())
    <div class="mb-4 rounded-2xl bg-amber-50 text-amber-900 px-4 py-3 text-sm flex flex-wrap items-center justify-between gap-3">
        <p>Akun masih menunggu OTP. Admin bisa memverifikasi langsung agar peternak dapat masuk.</p>
        <form method="POST" action="{{ route('admin.users.verify', $farmer->user) }}">
            @csrf
            <button class="btn-primary !py-2">Verifikasi admin</button>
        </form>
    </div>
@endif
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
