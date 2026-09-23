@extends('layouts.wow')
@section('content')
@php
    $tabs = [
        'ringkasan' => 'Ringkasan',
        'bobot' => 'Bobot',
        'ai' => 'Pemeriksaan AI',
        'bcs' => 'BCS',
        'kesehatan' => 'Kesehatan',
        'vaksin' => 'Vaksin',
        'pakan' => 'Pakan',
        'reproduksi' => 'Reproduksi',
        'kematian' => 'Kematian',
        'laporan' => 'Laporan',
    ];
    $b = $cattle->healthBadge();
    $dead = $cattle->isDead();
@endphp
<div class="card p-5 mb-5 flex flex-col md:flex-row gap-5">
    <img src="{{ $cattle->photoUrl() }}" class="h-28 w-28 rounded-2xl object-cover" alt="">
    <div class="flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-extrabold">{{ $cattle->code }}</h1>
            @if($dead)<span class="badge-bahaya">Meninggal</span>@endif
            <span class="{{ $b['tone']==='success'?'badge-sehat':($b['tone']==='warning'?'badge-suspek':'badge-bahaya') }}">{{ $b['label'] }}</span>
        </div>
        <p class="text-muted text-sm mt-1">{{ $cattle->name ?: 'Tanpa nama' }} · {{ $cattle->sexLabel() }} · {{ $cattle->breed?->name }} · {{ $cattle->farmer?->user?->name }}</p>
        <p class="text-sm mt-2">Bobot terakhir: <strong>{{ $cattle->latestWeight ? id_kg($cattle->latestWeight->weight_kg) : '—' }}</strong></p>
    </div>
    <div class="flex flex-wrap gap-2 self-start">
        @unless($dead)
            <a href="{{ route($panel.'.cattle.edit', $cattle) }}" class="btn-ghost">Ubah</a>
        @endunless
        <form method="POST" action="{{ route($panel.'.cattle.destroy', $cattle) }}" onsubmit="return confirm('Hapus data sapi ini dari daftar? Riwayat ikut diarsipkan. Untuk sapi yang mati, gunakan menu Kematian.')">
            @csrf
            @method('DELETE')
            <button class="btn-ghost text-danger">Hapus</button>
        </form>
    </div>
</div>

<div class="flex gap-1 overflow-x-auto mb-4">
    @foreach ($tabs as $key => $label)
        <a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => $key]) }}" class="tab-link {{ $tab===$key ? 'bg-primary text-white' : 'bg-white border border-line' }}">{{ $label }}</a>
    @endforeach
</div>

@if($tab==='ringkasan')
    <div class="grid md:grid-cols-3 gap-4">
        <article class="card p-5 md:col-span-2 space-y-2 text-sm">
            <p><span class="text-muted">Asal:</span> {{ $cattle->origin ?: '—' }}</p>
            <p><span class="text-muted">Warna:</span> {{ $cattle->color ?: '—' }}</p>
            <p><span class="text-muted">Lahir:</span> {{ id_date($cattle->birth_date) }} {{ $cattle->estimated_birth_date ? '(perkiraan)' : '' }}</p>
            <p><span class="text-muted">Masuk:</span> {{ id_date($cattle->entry_date) }}</p>
            <p><span class="text-muted">Catatan:</span> {{ $cattle->notes ?: '—' }}</p>
        </article>
        <article class="card p-5 text-sm space-y-3">
            <div>
                <p class="text-xs text-muted">Bobot Terakhir</p>
                <p class="font-extrabold">{{ $cattle->latestWeight ? id_kg($cattle->latestWeight->weight_kg) : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted">BCS Terakhir</p>
                @if($cattle->latestBcs)
                    <p class="font-extrabold">{{ number_format($cattle->latestBcs->score, 1) }} — {{ $cattle->latestBcs->category }}</p>
                    <p class="text-xs text-muted mt-1">{{ id_date($cattle->latestBcs->assessed_at) }}</p>
                @else
                    <p class="font-extrabold">Belum Dinilai</p>
                    @unless($dead)
                        <a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'bcs']) }}" class="text-primary text-xs font-semibold">Nilai Sekarang</a>
                    @endunless
                @endif
            </div>
            <div>
                <p class="text-xs text-muted">Kesehatan</p>
                <p class="font-semibold">{{ $b['label'] === 'Sehat' ? 'Tidak Terindikasi Lumpy Skin' : $b['label'] }}</p>
            </div>
        </article>
    </div>
    @if($cattle->latestBcs)
        <div class="mt-4">
            @include('partials.bcs-recommendation-card', [
                'cattle' => $cattle,
                'bcs' => $cattle->latestBcs,
                'href' => route($panel.'.cattle.show', [$cattle, 'tab' => 'bcs']),
            ])
        </div>
    @endif
@endif

@if($tab==='bobot')
    <div class="grid lg:grid-cols-3 gap-4">
        <article class="card p-5 lg:col-span-2"><div class="h-56"><canvas id="cowWeight"></canvas></div></article>
        <article class="card p-5">
            <h3 class="font-bold mb-3">Input bobot manual</h3>
            @unless($dead)
            <form method="POST" action="{{ route($panel.'.cattle.weights.store', $cattle) }}" class="space-y-3">
                @csrf
                <input type="number" step="0.1" name="weight_kg" class="input-wow" placeholder="kg" required>
                <input type="datetime-local" name="measured_at" class="input-wow">
                <textarea name="notes" class="input-wow" placeholder="Catatan"></textarea>
                <button class="btn-primary w-full">Simpan</button>
            </form>
            @endunless
            <p class="text-xs text-muted mt-3">Hasil AI bukan pengganti timbangan ternak.</p>
        </article>
    </div>
    <div class="card mt-4 overflow-x-auto">
        <table class="table-wow">
            <thead><tr><th class="col-no">No.</th><th>Tanggal</th><th>Bobot</th><th>Sumber</th></tr></thead>
            <tbody>
            @forelse ($report['weights'] as $w)
                <tr>
                    <td class="col-no">{{ $loop->iteration }}</td>
                    <td>{{ id_date($w->measured_at, true) }}</td>
                    <td>{{ id_kg($w->weight_kg) }}</td>
                    <td>{{ $w->sourceLabel() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-muted">Belum ada data bobot.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endif

@if($tab==='ai')
    <p class="text-sm text-muted mb-4">Hasil merupakan estimasi AI dan bukan pengganti timbangan ternak. Indikasi Lumpy Skin bukan diagnosis final.</p>
    @include('partials.ai-weight-photo-guide')
    @unless($dead)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach ([['weight','Estimasi Bobot AI', $panel.'.cattle.ai.weight'], ['lumpy','AI Pemeriksaan Kesehatan', $panel.'.cattle.ai.lumpy'], ['combined','Analisis Gabungan', $panel.'.cattle.ai.combined']] as $form)
            <form method="POST" enctype="multipart/form-data" action="{{ route($form[2], $cattle) }}" class="card p-4 sm:p-5 space-y-3">
                @csrf
                <h3 class="font-bold leading-snug">{{ $form[1] }}</h3>
                @if($form[0]==='weight')
                    <p class="text-sm text-muted leading-relaxed">Gunakan foto samping satu ekor, kepala hingga kaki terlihat.</p>
                @elseif($form[0]==='lumpy')
                    <p class="text-sm text-muted leading-relaxed">Pastikan sapi terlihat jelas di dalam foto.</p>
                @endif
                <x-file-input name="image" label="Pilih foto" required />
                <button class="btn-primary w-full">Unggah &amp; analisis</button>
            </form>
        @endforeach
    </div>
    @endunless
    <div class="space-y-3">
        @foreach ($report['ai'] as $exam)
            <article class="card p-4 text-sm">
                <div class="flex justify-between gap-3">
                    <div>
                        <p class="font-bold">{{ $exam->typeLabel() }} · {{ strtoupper($exam->status) }}</p>
                        <p class="text-muted">{{ id_date($exam->examined_at, true) }}</p>
                        @if($exam->estimated_weight_kg)<p>Bobot: <strong>{{ id_kg($exam->estimated_weight_kg) }}</strong> · Tingkat keyakinan AI: {{ $exam->detector_confidence ? round($exam->detector_confidence*100).'%' : '—' }}</p>@endif
                        @if($exam->lumpy_label)
                            <p>{{ $exam->lumpy_label }} · Tingkat keyakinan AI: {{ $exam->lumpy_probability ? round($exam->lumpy_probability*100,1).'%' : '—' }}</p>
                            @if($exam->lumpy_detected)
                                <p class="text-danger mt-1">Hasil AI menunjukkan indikasi Lumpy Skin. Disarankan melakukan pemeriksaan lanjutan oleh petugas kesehatan hewan.</p>
                            @endif
                        @endif
                        @if($exam->type==='combined' && is_array($exam->raw_response))
                            @if(!empty($exam->raw_response['weight_skipped']))
                                <p class="text-warning mt-1">Bobot dilewati: {{ $exam->raw_response['weight_skipped']['message'] ?? 'tidak diproses' }}</p>
                            @endif
                            @if(!empty($exam->raw_response['lumpy_skipped']))
                                <p class="text-warning mt-1">Lumpy dilewati: {{ $exam->raw_response['lumpy_skipped']['message'] ?? 'tidak diproses' }}</p>
                            @endif
                        @endif
                    </div>
                    @if($exam->imageUrl())<img src="{{ $exam->imageUrl() }}" class="h-16 w-16 rounded-lg object-cover">@endif
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-2">
                        @if($exam->lumpy_label && !$dead)
                    <form method="POST" action="{{ route($panel.'.cattle.ai.health', $cattle) }}">
                        @csrf
                        <input type="hidden" name="examination_id" value="{{ $exam->id }}">
                        <button class="text-primary text-xs font-semibold">Simpan ke Riwayat Kesehatan</button>
                    </form>
                @endif
                @if($exam->estimated_weight_kg && !$dead)
                    <a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'bcs']) }}" class="text-primary text-xs font-semibold hover:underline">Lanjutkan Penilaian BCS</a>
                @endif
                    <form method="POST" action="{{ route($panel.'.cattle.ai.destroy', $exam) }}" onsubmit="return confirm('Hapus indikasi/pemeriksaan AI ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold">Hapus</button></form>
                </div>
            </article>
        @endforeach
    </div>
@endif

@if($tab==='bcs')
    @include('cattle.partials.bcs-tab')
@endif

@if($tab==='kesehatan')
    @php $editHealth = $editHealth ?? null; @endphp
    @if(! $dead || $editHealth)
    <form method="POST" action="{{ $editHealth ? route($panel.'.cattle.health.update', $editHealth) : route($panel.'.cattle.health.store', $cattle) }}" class="card p-5 grid sm:grid-cols-2 gap-3 mb-4">
        @csrf
        @if($editHealth) @method('PUT') @endif
        <p class="sm:col-span-2 text-sm font-semibold">{{ $editHealth ? 'Ubah catatan (manual)' : 'Catat kesehatan (manual)' }}</p>
        <input name="type" class="input-wow" placeholder="Jenis (penyakit, cedera, lumpy)" required value="{{ old('type', $editHealth?->type) }}">
        <input name="title" class="input-wow" placeholder="Judul" required value="{{ old('title', $editHealth?->title) }}">
        <input name="medicine" class="input-wow" placeholder="Obat" value="{{ old('medicine', $editHealth?->medicine) }}">
        <input name="veterinarian" class="input-wow" placeholder="Dokter/petugas" value="{{ old('veterinarian', $editHealth?->veterinarian) }}">
        <input type="date" name="occurred_at" class="input-wow" value="{{ old('occurred_at', optional($editHealth?->occurred_at)?->format('Y-m-d')) }}">
        <input name="status" class="input-wow" placeholder="Status (sehat, perlu_pemeriksaan)" value="{{ old('status', $editHealth?->status) }}">
        <textarea name="description" class="input-wow sm:col-span-2" placeholder="Deskripsi">{{ old('description', $editHealth?->description) }}</textarea>
        <div class="sm:col-span-2 flex flex-wrap gap-3">
            <button class="btn-primary">{{ $editHealth ? 'Simpan perubahan' : 'Simpan' }}</button>
            @if($editHealth)<a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'kesehatan']) }}" class="btn-ghost">Batal</a>@endif
        </div>
    </form>
    @unless($dead)
    <form method="POST" enctype="multipart/form-data" action="{{ $editHealth ? route($panel.'.cattle.health.ai-update', $editHealth) : route($panel.'.cattle.health.ai', $cattle) }}" class="card p-5 space-y-3 mb-4">
        @csrf
        <p class="text-sm font-semibold">{{ $editHealth ? 'Atau perbarui dengan foto AI' : 'Atau isi dari foto AI Lumpy Skin' }}</p>
        <p class="text-sm text-muted leading-relaxed">Pastikan sapi terlihat jelas. Hasil AI bukan diagnosis final.</p>
        <x-file-input name="image" label="Pilih foto" required />
        <button class="btn-primary w-full sm:w-auto">{{ $editHealth ? 'Analisis & perbarui catatan' : 'Analisis & simpan ke riwayat' }}</button>
    </form>
    @endunless
    @endif
    <div class="space-y-2">
        @forelse($report['health'] as $h)
            <article class="card p-4 text-sm flex justify-between gap-3">
                <div>
                    <p class="font-bold">{{ $h->title }}</p>
                    <p class="text-muted">{{ id_date($h->occurred_at) }} · {{ $h->type }}{{ $h->status ? ' · '.$h->status : '' }}</p>
                    <p>{{ $h->description }}</p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'kesehatan', 'health' => $h->id]) }}" class="text-primary text-xs font-semibold">Ubah</a>
                    <form method="POST" action="{{ route($panel.'.cattle.health.destroy', $h) }}" onsubmit="return confirm('Hapus catatan kesehatan ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold">Hapus</button></form>
                </div>
            </article>
        @empty
            <p class="text-sm text-muted">Belum ada catatan kesehatan.</p>
        @endforelse
    </div>
@endif

@if($tab==='vaksin')
    @php $editSchedule = $editSchedule ?? null; $editVaxRecord = $editVaxRecord ?? null; @endphp
    @if(! $dead || $editSchedule)
    <form method="POST" action="{{ $editSchedule ? route($panel.'.cattle.schedules.update', $editSchedule) : route($panel.'.cattle.schedules.store', $cattle) }}" class="card p-5 flex flex-wrap gap-3 mb-4">
        @csrf
        @if($editSchedule) @method('PUT') @endif
        <select name="vaccine_id" class="input-wow">@foreach($vaccines as $v)<option value="{{ $v->id }}" @selected(old('vaccine_id', $editSchedule?->vaccine_id)==$v->id)>{{ $v->name }}</option>@endforeach</select>
        <input type="date" name="scheduled_date" class="input-wow" required value="{{ old('scheduled_date', optional($editSchedule?->scheduled_date)?->format('Y-m-d')) }}">
        <button class="btn-primary">{{ $editSchedule ? 'Simpan perubahan' : 'Tambah jadwal' }}</button>
        @if($editSchedule)<a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'vaksin']) }}" class="btn-ghost">Batal</a>@endif
    </form>
    @endif
    @if($editVaxRecord)
    <form method="POST" action="{{ route($panel.'.cattle.vaccination-records.update', $editVaxRecord) }}" class="card p-5 flex flex-wrap gap-3 mb-4">
        @csrf
        @method('PUT')
        <select name="vaccine_id" class="input-wow">@foreach($vaccines as $v)<option value="{{ $v->id }}" @selected(old('vaccine_id', $editVaxRecord->vaccine_id)==$v->id)>{{ $v->name }}</option>@endforeach</select>
        <input type="datetime-local" name="administered_at" class="input-wow" required value="{{ old('administered_at', optional($editVaxRecord->administered_at)?->format('Y-m-d\TH:i')) }}">
        <button class="btn-primary">Simpan riwayat</button>
        <a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'vaksin']) }}" class="btn-ghost">Batal</a>
    </form>
    @endif
    <div class="grid md:grid-cols-2 gap-4">
        <article class="card p-4"><h3 class="font-bold mb-2">Jadwal</h3>
            @foreach($report['schedules'] as $s)
                <div class="flex justify-between gap-3 text-sm py-2 border-b border-line">
                    <span>{{ $s->vaccine?->name }} · {{ id_date($s->scheduled_date) }} ({{ $s->statusLabel() }})</span>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($s->status==='scheduled')
                            <form method="POST" action="{{ route($panel.'.vaccinations.complete', $s) }}">@csrf<button class="text-primary text-xs font-semibold">Selesai</button></form>
                        @endif
                        <a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'vaksin', 'schedule' => $s->id]) }}" class="text-primary text-xs font-semibold">Ubah</a>
                        <form method="POST" action="{{ route($panel.'.cattle.schedules.destroy', $s) }}" onsubmit="return confirm('Hapus jadwal vaksin ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold">Hapus</button></form>
                    </div>
                </div>
            @endforeach
        </article>
        <article class="card p-4"><h3 class="font-bold mb-2">Riwayat pemberian</h3>
            @foreach($report['vaccinations'] as $r)
                <div class="flex justify-between gap-3 text-sm py-2 border-b border-line">
                    <span>{{ $r->vaccine?->name }} · {{ id_date($r->administered_at) }}</span>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'vaksin', 'vax' => $r->id]) }}" class="text-primary text-xs font-semibold">Ubah</a>
                        <form method="POST" action="{{ route($panel.'.cattle.vaccination-records.destroy', $r) }}" onsubmit="return confirm('Hapus riwayat vaksin ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold">Hapus</button></form>
                    </div>
                </div>
            @endforeach
        </article>
    </div>
@endif

@if($tab==='pakan')
    @php $editFeed = $editFeed ?? null; @endphp
    @if(! $dead || $editFeed)
    <form method="POST" action="{{ $editFeed ? route($panel.'.cattle.feeds.update', $editFeed) : route($panel.'.cattle.feeds.store', $cattle) }}" class="card p-5 grid sm:grid-cols-4 gap-3 mb-4">
        @csrf
        @if($editFeed) @method('PUT') @endif
        <input name="feed_name" class="input-wow sm:col-span-2" placeholder="Nama pakan" required value="{{ old('feed_name', $editFeed?->feed_name) }}">
        <input name="quantity" class="input-wow" placeholder="Jumlah" value="{{ old('quantity', $editFeed?->quantity) }}">
        <input name="unit" class="input-wow" placeholder="kg" value="{{ old('unit', $editFeed?->unit) }}">
        <input name="cost" class="input-wow" placeholder="Biaya" value="{{ old('cost', $editFeed?->cost) }}">
        <input type="date" name="fed_at" class="input-wow" value="{{ old('fed_at', optional($editFeed?->fed_at)?->format('Y-m-d')) }}">
        <button class="btn-primary">{{ $editFeed ? 'Simpan perubahan' : 'Simpan' }}</button>
        @if($editFeed)<a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'pakan']) }}" class="btn-ghost">Batal</a>@endif
    </form>
    @endif
    <p class="text-sm mb-2">Total biaya: <strong>Rp {{ number_format($report['feed_cost_total']) }}</strong></p>
    <div class="card overflow-x-auto">
        <table class="table-wow">
            <thead><tr><th class="col-no">No.</th><th>Tanggal</th><th>Pakan</th><th>Jumlah</th><th>Biaya</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($report['feeds'] as $f)
                <tr>
                    <td class="col-no">{{ $loop->iteration }}</td>
                    <td>{{ id_date($f->fed_at) }}</td>
                    <td>{{ $f->feed_name }}</td>
                    <td>{{ $f->quantity }} {{ $f->unit }}</td>
                    <td>Rp {{ number_format($f->cost) }}</td>
                    <td>
                        <div class="flex gap-3">
                            <a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'pakan', 'feed' => $f->id]) }}" class="text-primary text-sm font-semibold hover:underline">Ubah</a>
                            <form method="POST" action="{{ route($panel.'.cattle.feeds.destroy', $f) }}" onsubmit="return confirm('Hapus catatan pakan ini?')">@csrf @method('DELETE')<button class="text-danger text-sm font-semibold hover:underline">Hapus</button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-muted">Belum ada pakan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endif

@if($tab==='reproduksi')
    @php $editRepro = $editRepro ?? null; @endphp
    @if(! $dead || $editRepro)
    <form method="POST" action="{{ $editRepro ? route($panel.'.cattle.reproduction.update', $editRepro) : route($panel.'.cattle.reproduction.store', $cattle) }}" class="card p-5 grid sm:grid-cols-2 gap-3 mb-4">
        @csrf
        @if($editRepro) @method('PUT') @endif
        <select name="type" class="input-wow">@foreach(\App\Models\ReproductionRecord::typeLabels() as $k=>$l)<option value="{{ $k }}" @selected(old('type', $editRepro?->type)===$k)>{{ $l }}</option>@endforeach</select>
        <input type="date" name="event_date" class="input-wow" required value="{{ old('event_date', optional($editRepro?->event_date)?->format('Y-m-d')) }}">
        <input name="partner_code" class="input-wow" placeholder="Kode pejantan" value="{{ old('partner_code', $editRepro?->partner_code) }}">
        <input name="inseminator" class="input-wow" placeholder="Inseminator" value="{{ old('inseminator', $editRepro?->inseminator) }}">
        <button class="btn-primary">{{ $editRepro ? 'Simpan perubahan' : 'Simpan' }}</button>
        @if($editRepro)<a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'reproduksi']) }}" class="btn-ghost">Batal</a>@endif
    </form>
    @endif
    @foreach($report['reproduction'] as $r)
        <article class="card p-4 text-sm mb-2 flex justify-between gap-3">
            <p><strong>{{ $r->typeLabel() }}</strong> · {{ id_date($r->event_date) }}</p>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route($panel.'.cattle.show', [$cattle, 'tab' => 'reproduksi', 'repro' => $r->id]) }}" class="text-primary text-xs font-semibold">Ubah</a>
                <form method="POST" action="{{ route($panel.'.cattle.reproduction.destroy', $r) }}" onsubmit="return confirm('Hapus catatan reproduksi ini?')">@csrf @method('DELETE')<button class="text-danger text-xs font-semibold">Hapus</button></form>
            </div>
        </article>
    @endforeach
@endif

@if($tab==='kematian')
    @if($cattle->mortality)
        <article class="card p-5"><p class="font-bold">Meninggal {{ id_date($cattle->mortality->died_at) }}</p><p>{{ $cattle->mortality->suspected_cause }} {{ $cattle->mortality->confirmed_cause }}</p><p>{{ $cattle->mortality->notes }}</p></article>
    @elseif(!$dead)
        <form method="POST" enctype="multipart/form-data" action="{{ route($panel.'.cattle.mortality.store', $cattle) }}" class="card p-5 max-w-lg space-y-3">
            @csrf
            <p class="text-sm text-muted">Mencatat kematian akan mengubah status sapi menjadi Meninggal. Histori tidak dihapus.</p>
            <input type="datetime-local" name="died_at" class="input-wow" required>
            <input name="suspected_cause" class="input-wow" placeholder="Penyebab dugaan">
            <input name="confirmed_cause" class="input-wow" placeholder="Penyebab dikonfirmasi">
            <textarea name="notes" class="input-wow"></textarea>
            <input type="file" name="attachment">
            <button class="btn-primary">Catat kematian</button>
        </form>
    @endif
@endif

@if($tab==='laporan')
    <div class="card p-5 mb-4">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 mb-4">
            <div>
                <h3 class="font-bold text-lg">Laporan {{ $cattle->code }}</h3>
                <p class="text-xs text-muted">{{ $period?->label() ?? 'Semua periode' }} · termasuk bobot, AI, BCS, kesehatan, vaksin, pakan, biaya, reproduksi, dan kematian.</p>
            </div>
            <a href="{{ route($panel.'.cattle.report.pdf', ($period?->query(['cattle' => $cattle]) ?? ['cattle' => $cattle])) }}" class="btn-primary self-start">Unduh PDF</a>
        </div>
        @include('partials.period-filter', [
            'period' => $period,
            'default' => 'all',
            'allowAll' => true,
            'hidden' => ['tab' => 'laporan'],
        ])
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4 text-sm">
            <div class="rounded-xl bg-primary-soft p-3"><p class="text-xs text-muted">Biaya pakan</p><p class="font-bold">{{ id_rupiah($report['feed_cost_total']) }}</p></div>
            <div class="rounded-xl bg-primary-soft p-3"><p class="text-xs text-muted">Catatan bobot</p><p class="font-bold">{{ $report['weights']->count() }}</p></div>
            <div class="rounded-xl bg-primary-soft p-3"><p class="text-xs text-muted">Pemeriksaan AI</p><p class="font-bold">{{ $report['ai']->count() }}</p></div>
            <div class="rounded-xl bg-primary-soft p-3"><p class="text-xs text-muted">Kematian</p><p class="font-bold">{{ $report['mortality'] ? id_date($report['mortality']->died_at) : 'Tidak ada' }}</p></div>
        </div>
    </div>
    <div class="card p-5">
        <h3 class="font-bold mb-4">Timeline</h3>
        @include('partials.cattle-timeline', ['timeline' => $report['timeline']])
    </div>
@endif
@endsection
@if($tab==='bobot')
@push('scripts')
<script>
const weights = @json($report['weights']->map(fn($w)=>['t'=>id_date($w->measured_at),'kg'=>$w->weight_kg]));
new Chart(document.getElementById('cowWeight'), {
    type:'line',
    data:{ labels: weights.map(w=>w.t), datasets:[{ data: weights.map(w=>w.kg), borderColor:'#16A36A', tension:.3, fill:true, backgroundColor:'rgba(22,163,106,.12)' }]},
    options:{ plugins:{legend:{display:false}} }
});
</script>
@endpush
@endif
