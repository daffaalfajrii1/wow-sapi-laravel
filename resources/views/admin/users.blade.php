@extends('layouts.wow')
@section('content')
<h1 class="text-2xl font-extrabold mb-4">Pengguna</h1>
<form method="POST" action="{{ route('admin.users.store') }}" class="card p-4 mb-4 grid sm:grid-cols-5 gap-2">
    @csrf
    <input name="name" class="input-wow" placeholder="Nama" required>
    <input name="email" type="email" class="input-wow" placeholder="Email" required>
    <input name="password" type="password" class="input-wow" placeholder="Kata sandi" required>
    <select name="role" class="input-wow"><option value="peternak">Peternak</option><option value="admin">Admin</option></select>
    <button class="btn-primary">Tambah</button>
    <input name="phone" class="input-wow sm:col-span-2" placeholder="Telepon (peternak)">
</form>
<div class="card overflow-hidden">
<div class="overflow-x-auto">
<table class="table-wow">
<thead><tr><th class="col-no">No.</th><th>Nama</th><th>Email</th><th>Peran</th></tr></thead>
<tbody>
@forelse ($users as $u)
<tr>
    <td class="col-no">{{ table_no($users, $loop->index) }}</td>
    <td>{{ $u->name }}</td>
    <td>{{ $u->email }}</td>
    <td>{{ $u->getRoleNames()->join(', ') }}</td>
</tr>
@empty
<tr><td colspan="4" class="px-4 py-8 text-muted">Belum ada pengguna.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="px-4 py-3">{{ $users->links() }}</div>
</div>
@endsection
