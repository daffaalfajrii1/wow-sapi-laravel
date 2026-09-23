@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Pengaturan</h1>
<div class="grid md:grid-cols-2 gap-4">
    <article class="card p-5 text-sm space-y-2">
        <h2 class="font-bold">Layanan AI FastAPI</h2>
        <p>URL: <code>{{ config('wowsapi.ai.url') }}</code></p>
        <p>Status: @if($ai['reachable'] ?? false)<span class="badge-sehat">Terhubung</span>@else<span class="badge-bahaya">Tidak terhubung</span>@endif</p>
        <p class="text-muted">Dashboard tetap berjalan jika AI sedang mati. Pemeriksaan foto akan menampilkan pesan kesalahan yang jelas.</p>
    </article>
    <article class="card p-5 text-sm">
        <h2 class="font-bold mb-2">Aplikasi</h2>
        <p>Nama: {{ config('app.name') }}</p>
        <p>Database: {{ config('database.connections.mysql.database') }}</p>
        <p>Locale: {{ config('app.locale') }}</p>
    </article>
</div>
@endsection
