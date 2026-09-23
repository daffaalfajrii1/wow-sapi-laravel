@extends('layouts.wow')
@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Data Sapi</h1>
        <p class="text-sm text-muted">1 sapi = 1 profil digital</p>
    </div>
    <a href="{{ route($panel.'.cattle.create') }}" class="btn-primary"><x-icon name="plus" class="w-4 h-4" /> Tambah Sapi</a>
</div>
<form class="mb-4" method="get">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode, nama, atau peternak..." class="input-wow max-w-md">
</form>
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-wow">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th>Kode</th>
                    <th>Foto</th>
                    <th>Peternak</th>
                    <th>Ras</th>
                    <th>Bobot</th>
                    <th>Kesehatan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($cattle as $row)
                @php
                    $b = $row->healthBadge();
                    $showUrl = route($panel.'.cattle.show', $row);
                @endphp
                <tr class="group cursor-pointer" onclick="window.location='{{ $showUrl }}'">
                    <td class="col-no">{{ table_no($cattle, $loop->index) }}</td>
                    <td><a class="font-semibold text-primary group-hover:underline" href="{{ $showUrl }}">{{ $row->code }}</a></td>
                    <td>
                        <a href="{{ $showUrl }}" class="inline-block" tabindex="-1">
                            <img src="{{ $row->photoUrl() }}" class="h-10 w-10 rounded-lg object-cover" alt="{{ $row->code }}">
                        </a>
                    </td>
                    <td>{{ $row->farmer?->user?->name }}</td>
                    <td>{{ $row->breed?->name }}</td>
                    <td>{{ $row->latestWeight ? id_kg($row->latestWeight->weight_kg) : '—' }}</td>
                    <td><span class="{{ $b['tone']==='success'?'badge-sehat':($b['tone']==='warning'?'badge-suspek':'badge-bahaya') }}">{{ $b['label'] }}</span></td>
                    <td>{{ $row->statusLabel() }}</td>
                    <td>
                        <div class="flex items-center gap-3" onclick="event.stopPropagation()">
                            <a href="{{ $showUrl }}" class="text-primary text-sm font-semibold hover:underline">Lihat</a>
                            <form method="POST" action="{{ route($panel.'.cattle.destroy', $row) }}" onsubmit="return confirm('Hapus data sapi ini dari daftar?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-danger text-sm font-semibold hover:underline">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-4 py-8 text-muted">Belum ada data sapi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $cattle->links() }}</div>
</div>
@endsection
