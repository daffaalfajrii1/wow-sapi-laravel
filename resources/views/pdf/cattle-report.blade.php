<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $cattle->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a2e22; margin: 0; }
        .header { border-bottom: 2px solid #16A36A; padding-bottom: 10px; margin-bottom: 14px; }
        .header img { height: 42px; }
        .muted { color: #5b6b62; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 13px; color: #0f7a4a; margin: 16px 0 6px; border-bottom: 1px solid #d7e8dc; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #d7e8dc; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #e8f6ee; font-size: 10px; }
        .meta td { border: 0; padding: 2px 0; }
        .kpi { width: 100%; margin: 8px 0 12px; }
        .kpi td { border: 1px solid #d7e8dc; background: #f6fbf8; width: 25%; }
        .kpi strong { display: block; font-size: 13px; }
        .empty { color: #5b6b62; font-style: italic; }
        .footer { margin-top: 18px; font-size: 9px; color: #5b6b62; border-top: 1px solid #d7e8dc; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        @if(is_file(public_path('images/wow-sapi-logo.png')))
            <img src="{{ public_path('images/wow-sapi-logo.png') }}" alt="WOW SAPI">
        @else
            <strong>WOW SAPI</strong>
        @endif
        <h1>Laporan Sapi {{ $cattle->code }}</h1>
        <div class="muted">Periode: {{ $period?->label() ?? 'Semua periode' }} · Dicetak {{ id_date($generatedAt, true) }}</div>
    </div>

    <table class="meta">
        <tr><td width="28%">Nama / status</td><td>{{ $cattle->name ?: 'Tanpa nama' }} · {{ $cattle->statusLabel() }} · {{ $cattle->sexLabel() }}</td></tr>
        <tr><td>Peternak</td><td>{{ $cattle->farmer?->user?->name }} · {{ $cattle->farmer?->farm_name }}</td></tr>
        <tr><td>Ras</td><td>{{ $cattle->breed?->name ?: '—' }}</td></tr>
        <tr><td>Kesehatan</td><td>{{ $cattle->healthBadge()['label'] }}</td></tr>
        <tr><td>Lahir / masuk</td><td>{{ id_date($cattle->birth_date) }} · {{ id_date($cattle->entry_date) }}</td></tr>
    </table>

    <table class="kpi">
        <tr>
            <td>Bobot terakhir<br><strong>{{ $cattle->latestWeight ? id_kg($cattle->latestWeight->weight_kg) : '—' }}</strong></td>
            <td>BCS terakhir<br><strong>{{ $cattle->latestBcs ? number_format((float) $cattle->latestBcs->score, 1).' · '.$cattle->latestBcs->category : '—' }}</strong></td>
            <td>Biaya pakan<br><strong>{{ id_rupiah($report['feed_cost_total']) }}</strong></td>
            <td>Kematian<br><strong>{{ $report['mortality'] ? id_date($report['mortality']->died_at) : 'Tidak ada' }}</strong></td>
        </tr>
    </table>

    <h2>1. Perkembangan bobot</h2>
    @if($report['weights']->isEmpty())
        <p class="empty">Tidak ada data bobot pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>No.</th><th>Tanggal</th><th>Bobot</th><th>Sumber</th></tr></thead>
            <tbody>
            @foreach($report['weights'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ id_date($row->measured_at, true) }}</td>
                    <td>{{ id_kg($row->weight_kg) }}</td>
                    <td>{{ $row->sourceLabel() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>2. Pemeriksaan AI</h2>
    @if($report['ai']->isEmpty())
        <p class="empty">Tidak ada pemeriksaan AI pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>No.</th><th>Tanggal</th><th>Jenis</th><th>Hasil</th></tr></thead>
            <tbody>
            @foreach($report['ai'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ id_date($row->examined_at, true) }}</td>
                    <td>{{ $row->type }}</td>
                    <td>{{ $row->timelineTitle() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>3. BCS</h2>
    @if($report['bcs']->isEmpty())
        <p class="empty">Tidak ada penilaian BCS pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>No.</th><th>Tanggal</th><th>Skor</th><th>Kategori</th><th>Penilai</th></tr></thead>
            <tbody>
            @foreach($report['bcs'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ id_date($row->assessed_at, true) }}</td>
                    <td>{{ number_format((float) $row->score, 1) }}</td>
                    <td>{{ $row->category }}</td>
                    <td>{{ $row->creator?->name ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>4. Kesehatan</h2>
    @if($report['health']->isEmpty())
        <p class="empty">Tidak ada catatan kesehatan pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>No.</th><th>Tanggal</th><th>Judul</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($report['health'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ id_date($row->occurred_at) }}</td>
                    <td>{{ $row->title }}</td>
                    <td>{{ $row->status ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>5. Vaksinasi</h2>
    @if($report['schedules']->isEmpty() && $report['vaccinations']->isEmpty())
        <p class="empty">Tidak ada data vaksinasi pada periode ini.</p>
    @else
        @if($report['schedules']->isNotEmpty())
            <p><strong>Jadwal</strong></p>
            <table>
                <thead><tr><th>No.</th><th>Tanggal</th><th>Vaksin</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($report['schedules'] as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ id_date($row->scheduled_date) }}</td>
                        <td>{{ $row->vaccine?->name }}</td>
                        <td>{{ $row->statusLabel() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
        @if($report['vaccinations']->isNotEmpty())
            <p><strong>Pemberian</strong></p>
            <table>
                <thead><tr><th>No.</th><th>Tanggal</th><th>Vaksin</th></tr></thead>
                <tbody>
                @foreach($report['vaccinations'] as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ id_date($row->administered_at) }}</td>
                        <td>{{ $row->vaccine?->name }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <h2>6. Pakan &amp; biaya</h2>
    @if($report['feeds']->isEmpty())
        <p class="empty">Tidak ada catatan pakan pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>No.</th><th>Tanggal</th><th>Pakan</th><th>Jumlah</th><th>Biaya</th></tr></thead>
            <tbody>
            @foreach($report['feeds'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ id_date($row->fed_at) }}</td>
                    <td>{{ $row->feed_name }}</td>
                    <td>{{ $row->quantity }} {{ $row->unit }}</td>
                    <td>{{ id_rupiah($row->cost) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4"><strong>Total biaya pakan</strong></td>
                <td><strong>{{ id_rupiah($report['feed_cost_total']) }}</strong></td>
            </tr>
            </tbody>
        </table>
    @endif

    <h2>7. Reproduksi</h2>
    @if($report['reproduction']->isEmpty())
        <p class="empty">Tidak ada catatan reproduksi pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>No.</th><th>Tanggal</th><th>Kejadian</th></tr></thead>
            <tbody>
            @foreach($report['reproduction'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ id_date($row->event_date) }}</td>
                    <td>{{ $row->typeLabel() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>8. Kematian</h2>
    @if(! $report['mortality'])
        <p class="empty">Tidak ada catatan kematian pada periode ini.</p>
    @else
        @php $m = $report['mortality']; @endphp
        <table>
            <tr><th width="28%">Tanggal</th><td>{{ id_date($m->died_at, true) }}</td></tr>
            <tr><th>Penyebab dugaan</th><td>{{ $m->suspected_cause ?: '—' }}</td></tr>
            <tr><th>Penyebab dikonfirmasi</th><td>{{ $m->confirmed_cause ?: '—' }}</td></tr>
            <tr><th>Catatan</th><td>{{ $m->notes ?: '—' }}</td></tr>
        </table>
    @endif

    <h2>9. Timeline</h2>
    @if($report['timeline']->isEmpty())
        <p class="empty">Tidak ada riwayat pada periode ini.</p>
    @else
        <table>
            <thead><tr><th>No.</th><th>Tanggal</th><th>Jenis</th><th>Keterangan</th></tr></thead>
            <tbody>
            @foreach($report['timeline'] as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ id_date($item['at'], true) }}</td>
                    <td>{{ ucfirst($item['kind']) }}</td>
                    <td>{{ $item['title'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">WOW SAPI · Timbang Lebih Mudah, Pantau Lebih Cerdas · Laporan ini dihasilkan otomatis dari data peternak.</div>
</body>
</html>
