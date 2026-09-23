@extends('layouts.wow')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-extrabold">Dashboard</h1>
    <p class="text-sm text-muted">Ringkasan ternak Anda.</p>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    @foreach ([
        ['Total sapi saya', $total, 'cow'],
        ['Sapi aktif', $aktif, 'heart'],
        ['Pemeriksaan AI bulan ini', $ai_bulan_ini, 'scan'],
        ['Perlu perhatian', $perlu_perhatian, 'alert'],
        ['Vaksin terdekat', $vaksin_terdekat?->scheduled_date ? id_date($vaksin_terdekat->scheduled_date) : '—', 'syringe'],
    ] as $c)
        <article class="card p-4">
            <div class="kpi-icon bg-primary-soft text-primary mb-2"><x-icon :name="$c[2]" class="w-5 h-5" /></div>
            <p class="text-xs text-muted">{{ $c[0] }}</p>
            <p class="text-xl font-extrabold">{{ $c[1] }}</p>
        </article>
    @endforeach
</div>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <article class="card p-5 xl:col-span-2">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
            <div>
                <h2 class="font-bold">Perkembangan Bobot</h2>
                <p class="text-xs text-muted">{{ $period->label() }}</p>
            </div>
            @include('partials.period-filter', [
                'period' => $period,
                'default' => '6m',
                'allowAll' => false,
                'herd' => $herd ?? collect(),
                'chartCattleId' => $chart_cattle_id ?? null,
            ])
        </div>
        <div class="h-56"><canvas id="w"></canvas></div>
    </article>
    <article class="card p-5">
        <h2 class="font-bold mb-3">Jadwal Vaksin</h2>
        <ul class="space-y-3 text-sm">
            @forelse ($schedules as $s)
                <li class="flex justify-between gap-2"><span>@if($s->cattle)<x-cattle-code :cattle="$s->cattle" panel="peternak" tab="vaksin" />@endif · {{ $s->vaccine?->name }}</span><span class="text-muted">{{ id_date($s->scheduled_date) }}</span></li>
            @empty
                <li class="text-muted">Belum ada jadwal.</li>
            @endforelse
        </ul>
    </article>
</div>
@if(($assessed_cattle ?? collect())->isNotEmpty())
<article class="card p-5 mt-4">
    <div class="flex items-end justify-between gap-3 mb-4">
        <div>
            <h2 class="font-bold">Rekomendasi BCS</h2>
            <p class="text-xs text-muted mt-0.5">Sapi yang sudah dinilai, lengkap dengan saran pemeliharaan.</p>
        </div>
    </div>
    <div class="grid lg:grid-cols-2 gap-4">
        @foreach ($assessed_cattle as $row)
            @include('partials.bcs-recommendation-card', [
                'cattle' => $row,
                'bcs' => $row->latestBcs,
                'href' => route('peternak.cattle.show', [$row, 'tab' => 'bcs']),
            ])
        @endforeach
    </div>
</article>
@endif
<article class="card p-5 mt-4">
    <div class="flex justify-between mb-3"><h2 class="font-bold">Sapi saya</h2><a class="text-primary text-sm font-semibold" href="{{ route('peternak.cattle.index') }}">Lihat Semua</a></div>
    <div class="overflow-x-auto">
        <table class="table-wow">
            <thead><tr><th class="col-no">No.</th><th>Kode</th><th>Ras</th><th>Bobot</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($latest_cattle as $row)
                @php
                    $b = $row->healthBadge();
                    $showUrl = route('peternak.cattle.show', $row);
                @endphp
                <tr class="group cursor-pointer" onclick="window.location='{{ $showUrl }}'">
                    <td class="col-no">{{ $loop->iteration }}</td>
                    <td><a class="font-semibold text-primary group-hover:underline" href="{{ $showUrl }}">{{ $row->code }}</a></td>
                    <td>{{ $row->breed?->name }}</td>
                    <td>{{ $row->latestWeight ? id_kg($row->latestWeight->weight_kg) : '—' }}</td>
                    <td><span class="{{ $b['tone']==='success'?'badge-sehat':($b['tone']==='warning'?'badge-suspek':'badge-bahaya') }}">{{ $b['label'] }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</article>
@endsection
@push('scripts')
<script>
const months = @json($weight_chart);
const values = months.map(m => m.avg).filter(v => v !== null && v !== undefined);
const canvas = document.getElementById('w');
if (!values.length) {
    canvas.parentElement.innerHTML = '<p class="text-sm text-muted text-center py-16">Belum ada catatan bobot di periode ini.</p>';
} else {
    const pad = Math.max(20, Math.round((Math.max(...values) - Math.min(...values)) * 0.25) || 30);
    const ymin = Math.max(0, Math.min(...values) - pad);
    const ymax = Math.max(...values) + pad;
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: months.map(m => m.label),
            datasets: [{
                data: months.map(m => m.avg ?? null),
                borderColor: '#16A36A',
                backgroundColor: (ctx) => {
                    const {chart} = ctx;
                    const {ctx: c, chartArea} = chart;
                    if (!chartArea) return 'rgba(22,163,106,.12)';
                    const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    g.addColorStop(0, 'rgba(22,163,106,.28)');
                    g.addColorStop(1, 'rgba(22,163,106,.02)');
                    return g;
                },
                fill: true,
                tension: .35,
                spanGaps: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#16A36A',
                pointBorderWidth: 2,
                borderWidth: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#12261C',
                    callbacks: { label: (item) => item.parsed.y == null ? '' : item.parsed.y.toFixed(1) + ' kg' }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#6B7C72', maxRotation: 0 } },
                y: {
                    beginAtZero: false,
                    suggestedMin: ymin,
                    suggestedMax: ymax,
                    grid: { color: 'rgba(215,230,220,.8)' },
                    ticks: { color: '#6B7C72', callback: v => v + ' kg' }
                }
            }
        }
    });
}
</script>
@endpush
