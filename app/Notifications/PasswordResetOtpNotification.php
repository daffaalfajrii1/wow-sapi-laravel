<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $ttlMinutes = 5,
        public ?string $resetUrl = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP atur ulang kata sandi WOW SAPI')
            ->view('emails.reset-otp', [
                'user' => $notifiable,
                'code' => $this->code,
                'ttl' => $this->ttlMinutes,
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
