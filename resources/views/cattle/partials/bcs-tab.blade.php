@php
    $latestWeight = $cattle->latestWeight;
    $previousWeight = $cattle->weightRecords()->orderByDesc('measured_at')->skip(1)->first();
    $detailId = request()->integer('record');
    $detail = $detailId ? $report['bcs']->firstWhere('id', $detailId) : $report['bcs']->first();
    $defaultScore = old('score', 3);
@endphp

<div class="grid lg:grid-cols-5 gap-4 mb-6">
    <article class="card p-5 lg:col-span-3 space-y-4">
        <div>
            <h2 class="text-xl font-extrabold">Penilaian Kondisi Tubuh (BCS)</h2>
            <p class="text-sm text-muted mt-1">Nilai kondisi tubuh sapi berdasarkan pengamatan fisik. Perhatikan tulang rusuk, tulang belakang, pinggul, pangkal ekor, dan lapisan lemak tubuh.</p>
        </div>
        <div class="rounded-2xl bg-primary-soft/60 p-4 text-sm grid sm:grid-cols-2 gap-2">
            <p class="sm:col-span-2 font-extrabold text-primary-dark">{{ $cattle->code }}</p>
            <p><span class="text-muted">Nama:</span> {{ $cattle->name ?: '—' }}</p>
            <p><span class="text-muted">Ras:</span> {{ $cattle->breed?->name ?: '—' }}</p>
            <p><span class="text-muted">Jenis Kelamin:</span> {{ $cattle->sexLabel() }}</p>
            <p><span class="text-muted">Umur:</span> {{ $cattle->ageLabel() }}</p>
            <p class="sm:col-span-2"><span class="text-muted">Tujuan Pemeliharaan:</span> {{ $cattle->getAttribute('purpose') ?: '—' }}</p>
        </div>

        @if($latestWeight)
            <div class="grid sm:grid-cols-3 gap-3 text-sm">
                <div class="rounded-2xl border border-line p-3">
                    <p class="text-xs text-muted">Bobot Terakhir</p>
                    <p class="text-lg font-extrabold">{{ id_kg($latestWeight->weight_kg) }}</p>
                </div>
                <div class="rounded-2xl border border-line p-3">
                    <p class="text-xs text-muted">Sumber</p>
                    <p class="font-semibold">{{ $latestWeight->sourceLabel() }}</p>
                </div>
                <div class="rounded-2xl border border-line p-3">
                    <p class="text-xs text-muted">Tanggal</p>
                    <p class="font-semibold">{{ id_date($latestWeight->measured_at) }}</p>
                </div>
            </div>
            @if($previousWeight)
                <p class="text-xs text-muted">Bobot sebelumnya {{ id_kg($previousWeight->weight_kg) }} · Perubahan
                    {{ ($latestWeight->weight_kg - $previousWeight->weight_kg) >= 0 ? '+' : '' }}{{ id_kg($latestWeight->weight_kg - $previousWeight->weight_kg) }}</p>
            @endif
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Belum ada data bobot untuk sapi ini.
                <a class="font-semibold text-primary underline ml-1" href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'ai']) }}">Lakukan Estimasi Bobot</a>
            </div>
        @endif

        @unless($dead)
        <form id="bcs-form" method="POST" enctype="multipart/form-data" action="{{ route($panel.'.cattle.bcs.store', $cattle) }}" class="space-y-4" x-data="{ score: {{ (float) $defaultScore }} }">
            @csrf
            <div>
                <p class="text-sm font-semibold mb-2">Kondisi Tubuh</p>
                <div class="flex justify-between text-xs text-muted px-1"><span>1</span><span>5</span></div>
                <input type="range" min="1" max="5" step="0.5" x-model.number="score" class="w-full accent-primary">
                <input type="hidden" name="score" :value="score">
                <p class="text-center text-4xl font-extrabold text-primary-dark mt-2" x-text="Number(score).toFixed(1)"></p>
                <p class="text-center text-sm font-bold tracking-wide text-primary uppercase" x-text="score < 2 ? 'Sangat Kurus' : (score < 2.5 ? 'Kurus' : (score <= 3.5 ? 'Ideal' : (score <= 4 ? 'Gemuk' : 'Sangat Gemuk')))"></p>
                <x-input-error :messages="$errors->get('score')" class="mt-2" />
            </div>
            <div>
                <x-input-label value="Tanggal Penilaian" />
                <input type="date" name="assessed_at" class="input-wow mt-1" value="{{ old('assessed_at', now()->toDateString()) }}">
            </div>
            <p class="text-sm"><span class="text-muted">Dinilai oleh</span> <strong>{{ auth()->user()->name }}</strong></p>
            <div>
                <x-input-label value="Catatan" />
                <textarea name="notes" class="input-wow mt-1" rows="3" placeholder="Catatan kondisi tubuh">{{ old('notes') }}</textarea>
            </div>
            <div>
                <x-input-label value="Foto kondisi tubuh" />
                <x-file-input name="image" class="mt-1" />
            </div>
            <button class="btn-primary w-full">Simpan Penilaian BCS</button>
        </form>
        @endunless
    </article>

    <aside class="lg:col-span-2 space-y-3">
        <article class="card p-5">
            <h3 class="font-bold mb-3">Panduan Penilaian</h3>
            <div class="space-y-2 text-sm">
                @foreach ([
                    ['1', 'Sangat Kurus', 'Tulang rusuk, tulang belakang, dan pinggul sangat jelas terlihat. Lapisan lemak sangat sedikit.'],
                    ['2', 'Kurus', 'Tulang rusuk dan pinggul masih terlihat jelas, namun tubuh mulai memiliki sedikit jaringan penutup.'],
                    ['3', 'Ideal', 'Tulang tidak terlalu menonjol dan kondisi tubuh terlihat proporsional.'],
                    ['4', 'Gemuk', 'Tulang sulit terlihat dan lapisan lemak mulai terlihat jelas.'],
                    ['5', 'Sangat Gemuk', 'Tubuh memiliki timbunan lemak yang tinggi dan struktur tulang hampir tidak terlihat.'],
                ] as $g)
                    <details class="rounded-xl border border-line px-3 py-2">
                        <summary class="cursor-pointer font-semibold">BCS {{ $g[0] }} — {{ $g[1] }}</summary>
                        <p class="text-muted mt-2">{{ $g[2] }}</p>
                    </details>
                @endforeach
            </div>
        </article>
    </aside>
</div>

@if($detail)
@php
    $detailScore = (float) $detail->score;
    $detailBadge = $detailScore < 2.5 ? 'badge-bahaya' : ($detailScore <= 3.5 ? 'badge-sehat' : 'badge-suspek');
@endphp
<article class="card p-5 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h3 class="font-bold text-lg">Hasil Penilaian Kondisi Tubuh</h3>
            <p class="text-xs text-muted mt-0.5">Penilaian terbaru untuk {{ $cattle->code }}</p>
        </div>
        <span class="{{ $detailBadge }}">{{ $detail->category }}</span>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm mb-4">
        <div class="rounded-2xl bg-primary-soft/60 p-3">
            <p class="text-xs text-muted">BCS</p>
            <p class="text-3xl font-extrabold text-primary-dark leading-none mt-1">{{ number_format($detail->score, 1) }} <span class="text-sm font-semibold text-muted">/ 5</span></p>
        </div>
        <div class="rounded-2xl border border-line p-3">
            <p class="text-xs text-muted">Kategori</p>
            <p class="font-bold mt-1">{{ $detail->category }}</p>
        </div>
        <div class="rounded-2xl border border-line p-3">
            <p class="text-xs text-muted">Bobot saat dinilai</p>
            <p class="font-bold mt-1">{{ id_kg($detail->weight_kg_snapshot) }}</p>
        </div>
        <div class="rounded-2xl border border-line p-3">
            <p class="text-xs text-muted">Tanggal penilaian</p>
            <p class="font-bold mt-1">{{ id_date($detail->assessed_at) }}</p>
        </div>
    </div>
    @if($detail->notes)<p class="text-sm mb-3"><span class="text-muted">Catatan:</span> {{ $detail->notes }}</p>@endif
    @if($detail->imageUrl())<img src="{{ $detail->imageUrl() }}" class="h-32 w-32 rounded-2xl object-cover mb-3" alt="">@endif
    <div class="rounded-2xl bg-primary-soft/70 p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-primary-dark mb-1">Rekomendasi</p>
        <p class="font-semibold mb-2 leading-relaxed">{{ $detail->recommendation_summary }}</p>
        <ul class="space-y-1.5 text-sm">
            @foreach ($detail->recommendations ?? [] as $item)
                <li class="flex gap-2 leading-relaxed"><x-icon name="leaf" class="w-4 h-4 text-primary shrink-0 mt-0.5" /> {{ $item }}</li>
            @endforeach
        </ul>
    </div>
</article>
@endif

<article class="card overflow-hidden">
    <div class="px-4 py-3 border-b border-line"><h3 class="font-bold">Riwayat BCS</h3></div>
    @if($report['bcs']->isEmpty())
        <div class="p-6 text-sm text-muted">
            Belum ada penilaian kondisi tubuh.
            @unless($dead)
                <a href="#bcs-form" class="text-primary font-semibold ml-1">Buat Penilaian Pertama</a>
            @endunless
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table-wow">
                <thead>
                    <tr>
                        <th class="col-no">No.</th>
                        <th>Tanggal</th>
                        <th>Bobot</th>
                        <th>BCS</th>
                        <th>Kategori</th>
                        <th>Penilai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($report['bcs'] as $r)
                    <tr>
                        <td class="col-no">{{ $loop->iteration }}</td>
                        <td>{{ id_date($r->assessed_at) }}</td>
                        <td>{{ id_kg($r->weight_kg_snapshot) }}</td>
                        <td>{{ number_format($r->score, 1) }}</td>
                        <td>{{ $r->category }}</td>
                        <td>{{ $r->creator?->name ?? '—' }}</td>
                        <td><a class="text-primary font-semibold hover:underline" href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'bcs', 'record' => $r->id]) }}">Detail</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>
