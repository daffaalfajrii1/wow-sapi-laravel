@props(['title' => 'WOW SAPI'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#F7FAF9;font-family:'Plus Jakarta Sans',Segoe UI,Helvetica,Arial,sans-serif;color:#12352F;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7FAF9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 10px 40px rgba(7,91,85,0.08);">
                    <tr>
                        <td style="background:#ffffff;padding:24px 32px;text-align:center;border-bottom:1px solid #E6EEEB;">
                            <img src="{{ url('images/wow-sapi-logo.png') }}" alt="WOW SAPI" style="height:52px;width:auto;max-width:280px;display:inline-block;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px;font-size:12px;color:#6B7C77;line-height:1.6;">
                            Email ini dikirim otomatis oleh WOW SAPI. Jangan bagikan kode OTP kepada siapa pun.
                            <br><span style="color:#16A36A;font-weight:600;">Sapi Sehat, Peternak Sejahtera</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
