<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} — WOW SAPI</title>
    @include('partials.favicon')
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans">
<div class="min-h-screen grid lg:grid-cols-2">
    <div class="hidden lg:flex flex-col justify-between p-10 bg-primary-dark text-white relative overflow-hidden">
        <a href="/" class="relative z-10 inline-flex items-center self-start rounded-2xl bg-white px-4 py-3 shadow-soft">
            <x-wow-logo class="h-12 w-auto max-w-[280px]" />
        </a>
        <div class="relative z-10">
            <p class="text-3xl font-extrabold leading-tight">Dashboard peternakan cerdas</p>
            <p class="mt-4 text-white/80 max-w-md">AI estimasi bobot dan pemeriksaan indikasi Lumpy Skin — tanpa mengganti peran dokter hewan.</p>
        </div>
        <p class="text-sm text-white/60 relative z-10">Sapi Sehat, Peternak Sejahtera</p>
    </div>
    <div class="flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <a href="/" class="lg:hidden inline-block mb-2">
                <x-wow-logo class="h-12 w-auto max-w-[240px]" />
            </a>
            <div class="card p-8 mt-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
</body>
</html>
