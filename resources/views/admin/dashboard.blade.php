@extends('layouts.wow')

@section('content')
@php
    $todayLabel = now()->locale('id')->translatedFormat('l, d F Y');
    $k = $kpis;
@endphp
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-ink">Dashboard</h1>
        <p class="text-sm text-muted mt-1">Kelola data sapi dengan teknologi AI untuk peternakan yang lebih cerdas.</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="inline-flex items-center gap-2 bg-white border border-line rounded-2xl px-3 py-2 text-sm">
            <x-icon name="calendar" class="w-4 h-4 text-primary" />
            {{ $todayLabel }}
        </div>
        <p class="hidden md:block font-script text-primary-dark text-lg leading-tight">Bersama Teknologi untuk<br>Peternak Indonesia</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['Total Sapi', number_format($k['total_sapi']), $k['total_sapi_delta'], 'dari bulan lalu', 'cow', 'bg-primary-soft text-primary'],
            ['Peternak Aktif', number_format($k['peternak_aktif']), $k['peternak_delta'], 'dari bulan lalu', 'users', 'bg-emerald-50 text-emerald-700'],
            ['Scan AI Hari Ini', number_format($k['scan_hari_ini']), $k['scan_delta_pct'], 'dari kemarin', 'scan', 'bg-teal-50 text-teal-700', true],
            ['Kasus Kesehatan', number_format($k['kasus_kesehatan']), $k['kasus_delta_pct'], 'dari minggu lalu', 'heart', 'bg-rose-50 text-danger', true],
        ];
    @endphp
    @foreach ($cards as $i => $c)
        @php $pct = $c[6] ?? false; $up = $c[2] >= 0; $isHealth = $i === 3; @endphp
        <article class="card p-5 flex items-start gap-4">
            <div class="kpi-icon {{ $c[5] }}"><x-icon :name="$c[4]" class="w-6 h-6" /></div>
            <div>
                <p class="text-sm text-muted">{{ $c[0] }}</p>
                <p class="text-2xl font-extrabold mt-0.5">{{ $c[1] }}</p>
                <p class="text-xs mt-1 {{ ($isHealth ? !$up : $up) ? 'text-primary' : 'text-danger' }}">
                    {{ $up ? '↑' : '↓' }} {{ $pct ? abs($c[2]).'%' : (($c[2]>=0?'+':'').$c[2]) }} {{ $c[3] }}
                </p>
            </div>
        </article>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
    <article class="card p-5 xl:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="font-bold flex items-center gap-2"><x-icon name="chart" class="w-4 h-4 text-primary" /> Perkembangan Bobot Sapi</h2>
                <p class="text-xs text-muted">Rata-rata bobot sapi berdasarkan bulan</p>
            </div>
            <span class="text-xs border border-line rounded-full px-3 py-1">6 Bulan Terakhir</span>
        </div>
        <div class="h-56"><canvas id="weightChart"></canvas></div>
    </article>
    <article class="card p-5">
        <h2 class="font-bold mb-1">Deteksi Lumpy Skin (AI)</h2>
        <p class="text-xs text-muted mb-3">Hasil deteksi dari scan gambar sapi</p>
        <div class="flex items-center gap-4">
            <div class="relative h-36 w-36 mx-auto">
                <canvas id="lumpyChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-2xl font-extrabold text-primary">{{ $lumpy['pct_sehat'] }}%</span>
                    <span class="text-[11px] text-muted">Sehat</span>
                </div>
            </div>
            <ul class="text-sm space-y-2">
                <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-primary"></span> Sehat <strong class="ml-auto">{{ number_format($lumpy['sehat']) }}</strong></li>
                <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-warning"></span> Suspek <strong class="ml-auto">{{ number_format($lumpy['suspek']) }}</strong></li>
                <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-danger"></span> Positif Lumpy Skin <strong class="ml-auto">{{ number_format($lumpy['positif']) }}</strong></li>
            </ul>
        </div>
        <p class="mt-3 text-xs text-primary-dark bg-primary-soft rounded-xl px-3 py-2">Teknologi AI membantu deteksi dini. Kesehatan sapi lebih terpantau, risiko lebih rendah.</p>
    </article>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
    <article class="card p-5 xl:col-span-2 overflow-hidden">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="font-bold">Data Sapi Terbaru</h2>
                <p class="text-xs text-muted">5 data sapi terakhir yang di-scan</p>
            </div>
            <a href="{{ route('admin.cattle.index') }}" class="text-sm text-primary font-semibold">Lihat Semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-wow">
                <thead>
                    <tr>
                        <th class="col-no">No.</th>
                        <th>Kode Sapi</th>
                        <th>Foto</th>
                        <th>Peternak</th>
                        <th>Bobot</th>
                        <th>Status Kesehatan</th>
                        <th>Tanggal Scan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($latest_cattle as $row)
                    @php
                        $b = $row->healthBadge();
                        $showUrl = route('admin.cattle.show', $row);
                    @endphp
                    <tr class="group cursor-pointer" onclick="window.location='{{ $showUrl }}'">
                        <td class="col-no">{{ $loop->iteration }}</td>
                        <td class="font-semibold"><a href="{{ $showUrl }}" class="text-primary group-hover:underline">{{ $row->code }}</a></td>
                        <td><img src="{{ $row->photoUrl() }}" class="h-9 w-9 rounded-lg object-cover" alt="{{ $row->code }}"></td>
                        <td>{{ $row->farmer?->user?->name ?? $row->farmer?->farm_name }}</td>
                        <td>{{ $row->latestWeight ? id_kg($row->latestWeight->weight_kg) : '—' }}</td>
                        <td>
                            <span class="{{ $b['tone']==='success' ? 'badge-sehat' : ($b['tone']==='warning' ? 'badge-suspek' : 'badge-bahaya') }}">{{ $b['label'] }}</span>
                        </td>
                        <td class="text-muted">{{ id_date($row->latestLumpy?->examined_at ?? $row->latestWeight?->measured_at, true) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-muted">Belum ada data sapi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>
    <div class="space-y-4">
        <article class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold">Notifikasi</h2>
                <a href="{{ route('admin.notifications.index') }}" class="text-sm text-primary font-semibold">Lihat Semua</a>
            </div>
            <ul class="space-y-3 text-sm">
                @forelse (auth()->user()->notifications()->limit(4)->get() as $n)
                    <li class="flex gap-3">
                        <span class="h-8 w-8 rounded-full bg-primary-soft text-primary flex items-center justify-center shrink-0"><x-icon name="bell" class="w-4 h-4" /></span>
                        <div>
                            <p class="font-medium">{{ $n->data['title'] ?? 'Notifikasi' }}</p>
                            <p class="text-xs text-muted">{{ $n->data['body'] ?? $n->created_at->diffForHumans() }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-muted text-sm">Tidak ada notifikasi baru.</li>
                @endforelse
            </ul>
        </article>
        <article class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold">Jadwal Vaksin</h2>
                <a href="{{ route('admin.vaccinations.index') }}" class="text-sm text-primary font-semibold">Lihat Semua</a>
            </div>
            <ul class="space-y-3">
                @forelse ($schedules as $s)
                    @php $isToday = $s->scheduled_date->isToday(); @endphp
                    <li class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-3">
                            <span class="h-8 w-8 rounded-full bg-primary-soft text-primary flex items-center justify-center"><x-icon name="syringe" class="w-4 h-4" /></span>
                            <div>
                                <p class="font-semibold">@if($s->cattle)<x-cattle-code :cattle="$s->cattle" panel="admin" tab="vaksin" />@else—@endif</p>
                                <p class="text-xs text-muted">{{ $s->vaccine?->name }}</p>
                            </div>
                        </div>
                        <span class="text-xs {{ $isToday ? 'text-primary font-semibold' : 'text-muted' }}">{{ $isToday ? 'Hari ini' : id_date($s->scheduled_date) }}</span>
                    </li>
                @empty
                    <li class="text-muted text-sm">Tidak ada jadwal mendatang.</li>
                @endforelse
            </ul>
        </article>
    </div>
</div>
@if (!($ai_online ?? true))
    <p class="text-xs text-warning">Layanan AI (FastAPI :8003) tidak terhubung. Dashboard tetap dapat digunakan.</p>
@endif
@endsection

@push('scripts')
<script>
const months = @json($weight_chart);
const lumpy = @json($lumpy);
new Chart(document.getElementById('weightChart'), {
    type: 'line',
    data: {
        labels: months.map(m => m.label),
        datasets: [{
            data: months.map(m => m.avg || null),
            borderColor: '#16A36A',
            backgroundColor: 'rgba(22,163,106,.12)',
            fill: true,
            tension: .35,
            pointBackgroundColor: '#16A36A',
            pointRadius: 4,
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: false, grid: { color: '#E5ECE9' } }, x: { grid: { display: false } } } }
});
new Chart(document.getElementById('lumpyChart'), {
    type: 'doughnut',
    data: {
        labels: ['Sehat', 'Suspek', 'Positif'],
        datasets: [{ data: [lumpy.sehat, lumpy.suspek, lumpy.positif], backgroundColor: ['#16A36A', '#E8A93A', '#E85C67'], borderWidth: 0 }]
    },
    options: { cutout: '72%', plugins: { legend: { display: false } } }
});
</script>
@endpush
