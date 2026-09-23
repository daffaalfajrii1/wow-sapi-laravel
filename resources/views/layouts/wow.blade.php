<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — WOW SAPI</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
@php
    $panel = request()->routeIs('admin.*') ? 'admin' : 'peternak';
    $user = auth()->user();
    $unread = $user?->unreadNotifications()->count() ?? 0;
    $adminNav = [
        ['dashboard', 'home', 'Dashboard'],
        ['cattle.index', 'cow', 'Data Sapi'],
        ['farmers.index', 'users', 'Peternak'],
        ['examinations.index', 'scan', 'Pemeriksaan AI'],
        ['health.index', 'heart', 'Kesehatan'],
        ['vaccinations.index', 'syringe', 'Vaksinasi'],
        ['feeds.index', 'leaf', 'Pakan'],
        ['reproduction.index', 'dna', 'Reproduksi'],
        ['mortality.index', 'alert', 'Kematian'],
        ['reports.index', 'clipboard', 'Laporan'],
        ['breeds.index', 'database', 'Master Data'],
        ['users.index', 'user', 'Pengguna'],
        ['settings', 'settings', 'Pengaturan'],
    ];
    $farmerNav = [
        ['dashboard', 'home', 'Dashboard'],
        ['cattle.index', 'cow', 'Data Sapi'],
        ['examinations.index', 'scan', 'Pemeriksaan AI'],
        ['weights.index', 'scale', 'Perkembangan Bobot'],
        ['bcs.index', 'clipboard', 'BCS'],
        ['health.index', 'heart', 'Kesehatan'],
        ['vaccinations.index', 'syringe', 'Vaksinasi'],
        ['feeds.index', 'leaf', 'Pakan'],
        ['reproduction.index', 'dna', 'Reproduksi'],
        ['reports.index', 'file', 'Laporan'],
        ['notifications.index', 'bell', 'Notifikasi'],
        ['profile.show', 'user', 'Profil'],
    ];
    $nav = $panel === 'admin' ? $adminNav : $farmerNav;
    $prefix = $panel.'.';
@endphp
<body class="font-sans bg-surface min-h-screen" x-data="{ sidebar: false }">
<div class="min-h-screen lg:flex">
    <div x-show="sidebar" x-transition.opacity class="fixed inset-0 z-30 bg-ink/40 lg:hidden" @click="sidebar=false" style="display:none"></div>

    <aside class="fixed inset-y-0 left-0 z-40 w-[260px] bg-white border-r border-line flex flex-col transform transition-transform -translate-x-full lg:!translate-x-0 lg:static"
           :class="sidebar && '!translate-x-0'">
        <div class="px-4 pt-5 pb-4">
            <a href="{{ route($prefix.'dashboard') }}" class="block rounded-2xl bg-white px-2 py-1.5">
                <x-wow-logo class="h-11 w-auto max-w-full" />
            </a>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 space-y-0.5 pb-4">
            @foreach ($nav as [$route, $icon, $label])
                @php $active = request()->routeIs($prefix.$route) || request()->routeIs($prefix.str($route)->before('.').'.*'); @endphp
                <a href="{{ route($prefix.$route) }}" class="nav-item {{ $active ? 'nav-item-active' : 'nav-item-idle' }}">
                    <x-icon :name="$icon" class="w-[18px] h-[18px]" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="m-3 rounded-2xl bg-gradient-to-b from-primary-soft to-[#cfe8d8] border border-line p-3">
            <x-wow-logo class="w-full h-auto max-h-16" />
            <p class="mt-2 text-center font-script text-primary-dark text-lg leading-tight">Sapi Sehat<br>Peternak Sejahtera</p>
        </div>
    </aside>

    <div class="flex-1 min-w-0 flex flex-col">
        <header class="sticky top-0 z-20 bg-surface/90 backdrop-blur px-4 lg:px-8 py-4">
            <div class="flex items-center gap-3">
                <button class="lg:hidden p-2 rounded-xl border border-line bg-white" @click="sidebar=true">
                    <x-icon name="menu" class="w-5 h-5" />
                </button>
                <form action="{{ route($prefix.'search') }}" method="get" class="flex-1">
                    <label class="relative block">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"><x-icon name="search" class="w-4 h-4" /></span>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari sapi atau peternak..."
                               class="w-full pl-10 pr-4 py-2.5 rounded-2xl border-line bg-white text-sm shadow-soft focus:border-primary focus:ring-primary placeholder:truncate">
                    </label>
                </form>
                <a href="{{ route($prefix.'notifications.index') }}" class="relative h-11 w-11 rounded-2xl bg-white border border-line flex items-center justify-center text-ink">
                    <x-icon name="bell" class="w-5 h-5" />
                    @if ($unread)
                        <span class="absolute -top-1 -right-1 h-5 min-w-5 px-1 rounded-full bg-danger text-white text-[10px] flex items-center justify-center">{{ $unread }}</span>
                    @endif
                </a>
                <div class="hidden sm:flex items-center gap-2 bg-white border border-line rounded-2xl pl-2 pr-3 py-1.5">
                    <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : asset('images/admin-avatar.svg') }}" alt="" class="h-8 w-8 rounded-full object-cover">
                    <div class="leading-tight">
                        <p class="text-sm font-semibold">{{ $user->name }}</p>
                        <p class="text-[11px] text-muted">{{ $user->isAdmin() ? 'Administrator' : 'Peternak' }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="text-muted hover:text-danger" title="Keluar"><x-icon name="logout" class="w-4 h-4" /></button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 lg:px-8 pb-10">
            @if (session('status'))
                <div class="mb-4 rounded-2xl bg-primary-soft text-primary-dark px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-2xl bg-rose-50 text-danger px-4 py-3 text-sm">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
