@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-1">Notifikasi</h1>
<p class="text-sm text-muted mb-4">Peringatan vaksin H-1 (besok) dan Hari H (hari ini) muncul otomatis.</p>

@if(($alerts ?? collect())->isNotEmpty())
<div class="mb-4 space-y-2">
    <h2 class="text-sm font-bold text-primary-dark">Peringatan vaksin aktif</h2>
    @foreach ($alerts as $a)
        <article class="card p-4 {{ ($a['when_type'] ?? '') === 'h0' ? 'border-rose-200 bg-rose-50/40' : 'border-amber-200 bg-amber-50/40' }}">
            <div class="flex flex-wrap items-center gap-2">
                <span class="{{ ($a['when_type'] ?? '') === 'h0' ? 'badge-bahaya' : 'badge-suspek' }}">{{ $a['when'] }}</span>
                <p class="font-bold">{{ $a['title'] }}</p>
            </div>
            <p class="text-sm text-muted mt-1">{{ $a['body'] }} · {{ id_date($a['scheduled_date'] ?? null) }}</p>
        </article>
    @endforeach
</div>
@endif

<form method="POST" action="{{ route($panel.'.notifications.read') }}" class="mb-4">@csrf<button class="btn-ghost">Tandai semua dibaca</button></form>
<div class="space-y-2">
@forelse ($items as $n)
    @php $data = $n->data ?? []; @endphp
    <article class="card p-4 {{ $n->read_at ? '' : 'border-primary' }}">
        <div class="flex flex-wrap items-center gap-2">
            @if(($data['when_type'] ?? null) === 'h0')
                <span class="badge-bahaya">Hari H</span>
            @elseif(($data['when_type'] ?? null) === 'h1')
                <span class="badge-suspek">H-1</span>
            @endif
            <p class="font-bold">{{ $data['title'] ?? 'Notifikasi' }}</p>
        </div>
        <p class="text-sm text-muted mt-1">{{ $data['body'] ?? '' }} · {{ id_date($n->created_at, true) }}</p>
    </article>
@empty
    <p class="text-muted">Tidak ada notifikasi.</p>
@endforelse
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
