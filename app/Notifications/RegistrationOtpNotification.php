<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationOtpNotification extends Notification
{
    use Queueable;

    public function __construct(public string $code, public int $ttlMinutes = 5)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode verifikasi daftar WOW SAPI')
            ->view('emails.register-otp', [
                'user' => $notifiable,
                'code' => $this->code,
                'ttl' => $this->ttlMinutes,
            ]);
    }
}
