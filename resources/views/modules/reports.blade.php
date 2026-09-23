@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-1">Laporan per Sapi</h1>
<p class="text-sm text-muted mb-4">Pilih sapi dan rentang tanggal, lalu unduh PDF lengkap (bobot, AI, BCS, kesehatan, vaksin, pakan, biaya, reproduksi, kematian).</p>
<form method="get" class="mb-4 flex flex-col sm:flex-row gap-2">
    <select name="cattle_id" class="input-wow max-w-sm">
        <option value="">Pilih sapi</option>
        @foreach ($cattleList as $c)
            <option value="{{ $c->id }}" @selected($selected?->id===$c->id)>{{ $c->code }} — {{ $c->farmer?->user?->name }}</option>
        @endforeach
    </select>
    <input type="hidden" name="range" value="{{ $period->range }}">
    @if($period->range === 'custom')
        <input type="hidden" name="from" value="{{ $period->from?->toDateString() }}">
        <input type="hidden" name="to" value="{{ $period->to?->toDateString() }}">
    @endif
    <button class="btn-primary">Tampilkan</button>
</form>
<div class="mb-5">
    @include('partials.period-filter', [
        'period' => $period,
        'default' => 'all',
        'allowAll' => true,
        'hidden' => $selected ? ['cattle_id' => $selected->id] : [],
    ])
</div>

@if($report && $selected)
    @php
        $b = $selected->healthBadge();
        $showUrl = route($panel.'.cattle.show', $selected);
        $tabLinks = [
            'ringkasan' => 'Data',
            'ai' => 'AI',
            'bobot' => 'Bobot',
            'vaksin' => 'Vaksinasi',
            'kesehatan' => 'Kesehatan',
            'pakan' => 'Pakan',
            'reproduksi' => 'Reproduksi',
        ];
    @endphp
    <article class="card p-5 mb-5 flex flex-col md:flex-row gap-5">
        <a href="{{ $showUrl }}">
            <img src="{{ $selected->photoUrl() }}" class="h-28 w-28 rounded-2xl object-cover" alt="{{ $selected->code }}">
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $showUrl }}" class="text-xl font-extrabold text-primary hover:underline">{{ $selected->code }}</a>
                <span class="{{ $b['tone']==='success'?'badge-sehat':($b['tone']==='warning'?'badge-suspek':'badge-bahaya') }}">{{ $b['label'] }}</span>
                <span class="text-xs font-semibold text-muted bg-primary-soft px-2 py-0.5 rounded-full">{{ $selected->statusLabel() }}</span>
            </div>
            <p class="text-sm text-muted mt-1">{{ $selected->farmer?->user?->name }} · {{ $selected->farmer?->farm_name }} · {{ $selected->breed?->name }}</p>
            <p class="text-sm mt-2">Bobot terakhir: <strong>{{ $selected->latestWeight ? id_kg($selected->latestWeight->weight_kg) : '—' }}</strong>
                <span class="text-muted"> · Total biaya pakan {{ id_rupiah($report['feed_cost_total']) }}</span>
            </p>
            <p class="text-xs text-muted mt-1">Periode laporan: {{ $period->label() }}</p>
            <div class="flex flex-wrap gap-2 mt-4">
                <a href="{{ route($panel.'.reports.pdf', $period->query(['cattle' => $selected])) }}" class="btn-primary">Unduh PDF</a>
                @foreach ($tabLinks as $key => $label)
                    <a href="{{ route($panel.'.cattle.show', [$selected, 'tab' => $key]) }}" class="tab-link bg-white border border-line hover:bg-primary-soft hover:text-primary-dark">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </article>

    <article class="card p-5">
        <h2 class="font-bold text-lg mb-4">Timeline lengkap</h2>
        @include('partials.cattle-timeline', ['timeline' => $report['timeline']])
    </article>
@endif
@endsection
