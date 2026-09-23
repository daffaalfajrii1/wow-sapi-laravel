@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Hasil pencarian “{{ $q }}”</h1>
<div class="grid md:grid-cols-2 gap-4">
    <article class="card p-5">
        <h2 class="font-bold mb-3">Sapi</h2>
        <ul class="space-y-2 text-sm">
            @forelse ($cattle as $c)
                <li><a class="text-primary font-semibold" href="{{ route($panel.'.cattle.show', $c) }}">{{ $c->code }}</a> — {{ $c->farmer?->user?->name }}</li>
            @empty
                <li class="text-muted">Tidak ditemukan.</li>
            @endforelse
        </ul>
    </article>
    @if(auth()->user()->isAdmin())
    <article class="card p-5">
        <h2 class="font-bold mb-3">Peternak</h2>
        <ul class="space-y-2 text-sm">
            @forelse ($farmers as $f)
                <li><a class="text-primary font-semibold" href="{{ route('admin.farmers.show', $f) }}">{{ $f->user?->name }}</a> — {{ $f->farm_name }}</li>
            @empty
                <li class="text-muted">Tidak ditemukan.</li>
            @endforelse
        </ul>
    </article>
    @endif
</div>
@endsection
