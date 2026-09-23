<x-email-layout title="Atur ulang kata sandi — WOW SAPI">
    <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#16A36A;">Lupa kata sandi</p>
    <h1 style="margin:0 0 16px;font-size:22px;font-weight:800;color:#075B55;">Halo, {{ $user->name }}</h1>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#34544E;">
        Kami menerima permintaan untuk mengatur ulang kata sandi akun WOW SAPI Anda. Gunakan kode OTP di bawah ini.
    </p>
    <div style="text-align:center;margin:28px 0;padding:20px;border-radius:16px;background:#EAF8F1;">
        <p style="margin:0 0 8px;font-size:12px;color:#075B55;font-weight:600;">Kode OTP lupa password</p>
        <p style="margin:0;font-size:36px;font-weight:800;letter-spacing:0.28em;color:#16A36A;">{{ $code }}</p>
    </div>
    <p style="margin:0 0 20px;font-size:14px;color:#34544E;">
        Kode berlaku <strong>{{ $ttl }} menit</strong>. Jika Anda tidak meminta atur ulang, abaikan email ini.
    </p>
    @if (! empty($resetUrl))
        <p style="margin:0 0 8px;font-size:14px;color:#34544E;">Atau klik tautan berikut (opsional):</p>
        <p style="margin:0;">
            <a href="{{ $resetUrl }}" style="display:inline-block;background:#16A36A;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 20px;border-radius:12px;">Atur ulang kata sandi</a>
        </p>
    @endif
</x-email-layout>
