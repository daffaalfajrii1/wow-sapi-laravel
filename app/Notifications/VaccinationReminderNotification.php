<?php

namespace App\Notifications;

use App\Models\VaccinationSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VaccinationReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public VaccinationSchedule $schedule,
        public string $whenLabel,
        public string $whenType = 'h0',
    ) {
    }

    public function via(object $notifiable): array
    {
        // Database agar selalu muncul di web/mobile; email opsional sering gagal di server tanpa SMTP.
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $cattle = $this->schedule->cattle;
        $vaccine = $this->schedule->vaccine?->name ?? 'Vaksin';
        $date = $this->schedule->scheduled_date?->translatedFormat('d F Y') ?? '—';
        $isToday = $this->whenType === 'h0';

        $title = $isToday
            ? 'Peringatan vaksin hari ini (Hari H)'
            : 'Peringatan vaksin besok (H-1)';

        $body = $isToday
            ? "Hari ini jadwal vaksin {$vaccine} untuk sapi {$cattle?->code}. Segera lakukan vaksinasi."
            : "Besok ({$date}) jadwal vaksin {$vaccine} untuk sapi {$cattle?->code}. Siapkan vaksin dan jadwal petugas.";

        return [
            'title' => $title,
            'body' => $body,
            'when' => $this->whenLabel,
            'when_type' => $this->whenType,
            'cattle_id' => $cattle?->id,
            'cattle_code' => $cattle?->code,
            'vaccine' => $vaccine,
            'scheduled_date' => optional($this->schedule->scheduled_date)?->toDateString(),
            'schedule_id' => $this->schedule->id,
            'kind' => 'vaccine_reminder',
            'url' => $notifiable->isAdmin()
                ? route('admin.vaccinations.index')
                : route('peternak.vaccinations.index'),
        ];
    }
}
