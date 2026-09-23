<x-email-layout title="Kode verifikasi daftar — WOW SAPI">
    <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#16A36A;">Verifikasi email</p>
    <h1 style="margin:0 0 16px;font-size:22px;font-weight:800;color:#075B55;">Halo, {{ $user->name }}</h1>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#34544E;">
        Terima kasih telah mendaftar di WOW SAPI. Masukkan kode OTP berikut untuk memverifikasi email Anda. Kode ini hanya digunakan saat daftar, sekali saja.
    </p>
    <div style="text-align:center;margin:28px 0;padding:20px;border-radius:16px;background:#EAF8F1;">
        <p style="margin:0 0 8px;font-size:12px;color:#075B55;font-weight:600;">Kode verifikasi 6 digit</p>
        <p style="margin:0;font-size:36px;font-weight:800;letter-spacing:0.28em;color:#16A36A;">{{ $code }}</p>
    </div>
    <p style="margin:0;font-size:14px;color:#34544E;">
        Kode berlaku <strong>{{ $ttl }} menit</strong>. Jika Anda tidak mendaftar, abaikan email ini.
    </p>
</x-email-layout>
