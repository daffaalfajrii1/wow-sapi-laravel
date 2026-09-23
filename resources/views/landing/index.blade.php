<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WOW SAPI — Timbang Lebih Mudah, Pantau Lebih Cerdas</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-surface text-ink">
<header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-line">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="block max-w-[220px]">
            <x-wow-logo class="h-11 w-auto max-w-full" />
        </a>
        <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
            <a href="#fitur" class="hover:text-primary">Fitur</a>
            <a href="#cara" class="hover:text-primary">Cara Kerja</a>
            <a href="#manfaat" class="hover:text-primary">Manfaat</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="btn-ghost">Masuk</a>
            <a href="{{ route('register') }}" class="btn-primary">Daftar</a>
        </div>
    </div>
</header>

<section class="max-w-6xl mx-auto px-4 py-16 grid md:grid-cols-2 gap-10 items-center">
    <div>
        <p class="font-script text-2xl text-primary-dark mb-2">Bersama Teknologi untuk Peternak Indonesia</p>
        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">Timbang Lebih Mudah,<br>Pantau Lebih Cerdas</h1>
        <p class="mt-4 text-muted text-lg">WOW SAPI membantu peternak mendata sapi, menaksir bobot dari foto, dan memantau indikasi Lumpy Skin dengan AI — tanpa mengganti peran dokter hewan.</p>
        <div class="mt-6 flex gap-3">
            <a href="{{ route('register') }}" class="btn-primary">Mulai Gratis</a>
            <a href="{{ route('login') }}" class="btn-ghost">Masuk Dashboard</a>
        </div>
    </div>
    <div class="card overflow-hidden bg-gradient-to-b from-white to-primary-soft p-8 flex items-center justify-center min-h-[20rem]">
        <x-wow-logo class="w-full max-w-md h-auto" />
    </div>
</section>

<section id="fitur" class="bg-white border-y border-line py-16">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-2xl font-extrabold mb-8">Fitur unggulan</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([
                ['scan', 'AI Estimasi Bobot', 'Taksir bobot dari foto, tepat 1 sapi.'],
                ['heart', 'AI Pemeriksaan Kesehatan', 'Indikasi Lumpy Skin, bukan diagnosis final.'],
                ['chart', 'Analisis Gabungan', 'Bobot dan lumpy dalam satu unggahan.'],
                ['cow', 'Data Sapi Digital', 'Satu sapi, satu profil lengkap.'],
                ['clipboard', 'BCS', 'Penilaian kondisi tubuh skala 1–5.'],
                ['syringe', 'Jadwal Vaksin', 'Pengingat H-7, H-1, dan Hari H.'],
                ['dna', 'Reproduksi', 'Birahi, IB, kebuntingan, kelahiran.'],
                ['file', 'Laporan Perkembangan', 'Timeline lengkap per ekor.'],
            ] as $f)
                <article class="card p-5">
                    <div class="kpi-icon bg-primary-soft text-primary mb-3"><x-icon :name="$f[0]" class="w-5 h-5" /></div>
                    <h3 class="font-bold">{{ $f[1] }}</h3>
                    <p class="text-sm text-muted mt-1">{{ $f[2] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="cara" class="max-w-6xl mx-auto px-4 py-16">
    <h2 class="text-2xl font-extrabold mb-8">Cara kerja</h2>
    <ol class="grid md:grid-cols-3 gap-4">
        @foreach (['Daftar sebagai peternak dan catat profil sapi.', 'Unggah foto — Laravel memanggil AI FastAPI secara internal.', 'Lihat histori bobot, kesehatan, vaksin, dan laporan.'] as $i => $t)
            <li class="card p-5"><span class="text-primary font-extrabold text-2xl">0{{ $i+1 }}</span><p class="mt-2">{{ $t }}</p></li>
        @endforeach
    </ol>
</section>

<section id="manfaat" class="bg-primary-dark text-white py-16">
    <div class="max-w-6xl mx-auto px-4 grid md:grid-cols-3 gap-6">
        <div><p class="text-4xl font-extrabold">1 foto</p><p class="text-white/80">untuk bobot & pemeriksaan</p></div>
        <div><p class="text-4xl font-extrabold">Histori</p><p class="text-white/80">perkembangan per ekor</p></div>
        <div><p class="text-4xl font-extrabold">Pengingat</p><p class="text-white/80">vaksin ke aplikasi Flutter</p></div>
    </div>
</section>

<footer class="py-8 text-center text-sm text-muted">
    © {{ date('Y') }} WOW SAPI · Timbang Lebih Mudah, Pantau Lebih Cerdas
</footer>
</body>
</html>
