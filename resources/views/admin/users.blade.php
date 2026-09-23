@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-1">Pengguna</h1>
<p class="text-sm text-muted mb-4">Tambah akun, ganti kata sandi, atau verifikasi peternak tanpa OTP.</p>

<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.users.index') }}" class="tab-link {{ $filter !== 'pending' ? 'bg-primary text-white' : 'bg-white border border-line text-ink' }}">Semua</a>
    <a href="{{ route('admin.users.index', ['filter' => 'pending']) }}" class="tab-link {{ $filter === 'pending' ? 'bg-primary text-white' : 'bg-white border border-line text-ink' }}">
        Menunggu verifikasi
        @if ($pendingCount)
            <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-100 px-1.5 text-[11px] font-bold text-amber-800">{{ $pendingCount }}</span>
        @endif
    </a>
</div>

<form method="POST" action="{{ route('admin.users.store') }}" class="card p-4 mb-4 grid sm:grid-cols-5 gap-2">
    @csrf
    <input name="name" class="input-wow" placeholder="Nama" value="{{ old('name') }}" required>
    <input name="email" type="email" class="input-wow" placeholder="Email" value="{{ old('email') }}" required>
    <input name="password" type="password" class="input-wow" placeholder="Kata sandi" required>
    <select name="role" class="input-wow">
        <option value="peternak" @selected(old('role', 'peternak') === 'peternak')>Peternak</option>
        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
    </select>
    <button class="btn-primary">Tambah</button>
    <input name="phone" class="input-wow sm:col-span-2" placeholder="Telepon (peternak)" value="{{ old('phone') }}">
    <p class="sm:col-span-5 text-xs text-muted">Akun yang ditambah admin langsung terverifikasi dan bisa masuk tanpa OTP.</p>
</form>

<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead>
<tr>
    <th class="col-no">No.</th>
    <th>Nama</th>
    <th>Email</th>
    <th>Peran</th>
    <th>Status</th>
    <th>Kata sandi</th>
</tr>
</thead>
<tbody>
@forelse ($users as $u)
<tr>
    <td class="col-no">{{ table_no($users, $loop->index) }}</td>
    <td class="font-semibold">{{ $u->name }}</td>
    <td>{{ $u->email }}</td>
    <td>{{ $u->getRoleNames()->join(', ') ?: '—' }}</td>
    <td>
        @if ($u->hasVerifiedEmail())
            <span class="badge-sehat">Terverifikasi</span>
        @else
            <div class="flex flex-col gap-2">
                <span class="badge-suspek">Menunggu OTP</span>
                <form method="POST" action="{{ route('admin.users.verify', $u) }}">
                    @csrf
                    <button class="btn-primary !py-1.5 !px-3 text-xs">Verifikasi admin</button>
                </form>
            </div>
        @endif
    </td>
    <td>
        <form method="POST" action="{{ route('admin.users.password', $u) }}" class="flex flex-col sm:flex-row gap-2 min-w-[220px]">
            @csrf
            @method('PUT')
            <input type="password" name="password" class="input-wow" placeholder="Sandi baru" required>
            <input type="password" name="password_confirmation" class="input-wow" placeholder="Ulangi" required>
            <button class="btn-ghost !py-2 whitespace-nowrap">Simpan</button>
        </form>
    </td>
</tr>
@empty
<tr><td colspan="6" class="px-4 py-8 text-muted">Belum ada pengguna.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $users->links() }}</div>
</div>
@endsection
