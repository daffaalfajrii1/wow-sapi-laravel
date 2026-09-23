<?php

namespace App\Notifications;

use App\Models\VaccinationSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VaccinationReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public VaccinationSchedule $schedule,
        public string $whenLabel,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cattle = $this->schedule->cattle;
        $vaccine = $this->schedule->vaccine?->name ?? 'Vaksin';

        return (new MailMessage)
            ->subject('Pengingat vaksin '.$this->whenLabel)
            ->greeting('Halo '.$notifiable->name)
            ->line("Jadwal vaksin {$vaccine} untuk sapi {$cattle?->code} {$this->whenLabel}.")
            ->line('Tanggal: '.$this->schedule->scheduled_date->translatedFormat('d F Y'))
            ->salutation('Salam, Tim WOW SAPI');
    }

    public function toArray(object $notifiable): array
    {
        $cattle = $this->schedule->cattle;
        $vaccine = $this->schedule->vaccine?->name ?? 'Vaksin';

        return [
            'title' => 'Pengingat vaksin '.$this->whenLabel,
            'body' => "{$cattle?->code} — {$vaccine}",
            'cattle_id' => $cattle?->id,
            'schedule_id' => $this->schedule->id,
            'url' => $notifiable->isAdmin()
                ? route('admin.vaccinations.index')
                : route('peternak.vaccinations.index'),
        ];
    }
}
