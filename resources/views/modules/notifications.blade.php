@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Notifikasi</h1>
<form method="POST" action="{{ route($panel.'.notifications.read') }}" class="mb-4">@csrf<button class="btn-ghost">Tandai semua dibaca</button></form>
<div class="space-y-2">
@forelse ($items as $n)
    <article class="card p-4 {{ $n->read_at ? '' : 'border-primary' }}">
        <p class="font-bold">{{ $n->data['title'] ?? 'Notifikasi' }}</p>
        <p class="text-sm text-muted">{{ $n->data['body'] ?? '' }} · {{ id_date($n->created_at, true) }}</p>
    </article>
@empty
    <p class="text-muted">Tidak ada notifikasi.</p>
@endforelse
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
