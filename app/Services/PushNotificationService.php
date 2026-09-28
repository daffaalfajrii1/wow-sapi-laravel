<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use App\Models\VaccinationSchedule;
use App\Models\VaccineReminderLog;
use App\Notifications\VaccinationReminderNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotificationService
{
    /**
     * Kirim pengingat vaksin: H-1 (besok) dan Hari H (hari ini).
     * Idempotent lewat vaccine_reminder_logs.
     */
    public function sendVaccineReminders(?\DateTimeInterface $today = null): int
    {
        $today = Carbon::parse($today ?? now())->startOfDay();
        $sent = 0;

        $map = [
            'h1' => $today->copy()->addDay(),
            'h0' => $today->copy(),
        ];

        foreach ($map as $type => $date) {
            $schedules = VaccinationSchedule::query()
                ->with(['cattle.farmer.user', 'vaccine'])
                ->where('status', 'scheduled')
                ->whereDate('scheduled_date', $date)
                ->get();

            foreach ($schedules as $schedule) {
                if ($this->alreadySent($schedule, $type)) {
                    continue;
                }

                if ($this->notifySchedule($schedule, $type)) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
     * Panggil saat jadwal baru dibuat — langsung notif jika H-1 / Hari H.
     */
    public function remindIfDueSoon(VaccinationSchedule $schedule): void
    {
        $schedule->loadMissing(['cattle.farmer.user', 'vaccine']);
        if ($schedule->status !== 'scheduled' || ! $schedule->scheduled_date) {
            return;
        }

        $today = now()->startOfDay();
        $date = $schedule->scheduled_date->copy()->startOfDay();

        $type = match (true) {
            $date->equalTo($today) => 'h0',
            $date->equalTo($today->copy()->addDay()) => 'h1',
            default => null,
        };

        if ($type === null || $this->alreadySent($schedule, $type)) {
            return;
        }

        $this->notifySchedule($schedule, $type);
    }

    /**
     * Alert siap tampil di dashboard/mobile (tanpa menunggu cron).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function upcomingAlertsForCattleIds(iterable $cattleIds): Collection
    {
        $ids = collect($cattleIds)->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        return VaccinationSchedule::query()
            ->with(['cattle', 'vaccine'])
            ->whereIn('cattle_id', $ids)
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_date', [$today->toDateString(), $tomorrow->toDateString()])
            ->orderBy('scheduled_date')
            ->get()
            ->map(function (VaccinationSchedule $s) use ($today) {
                $isToday = $s->scheduled_date->isSameDay($today);
                $when = $isToday ? 'Hari H' : 'H-1';

                return [
                    'id' => $s->id,
                    'when' => $when,
                    'when_type' => $isToday ? 'h0' : 'h1',
                    'title' => $isToday
                        ? 'Vaksin hari ini'
                        : 'Vaksin besok',
                    'body' => trim(($s->cattle?->code ?? '').' — '.($s->vaccine?->name ?? 'Vaksin')),
                    'cattle_id' => $s->cattle_id,
                    'cattle_code' => $s->cattle?->code,
                    'cattle_name' => $s->cattle?->name,
                    'vaccine' => $s->vaccine?->name,
                    'scheduled_date' => optional($s->scheduled_date)?->toDateString(),
                    'status' => $s->status,
                    'status_label' => $s->statusLabel(),
                ];
            })
            ->values();
    }

    protected function alreadySent(VaccinationSchedule $schedule, string $type): bool
    {
        return VaccineReminderLog::query()
            ->where('vaccination_schedule_id', $schedule->id)
            ->where('reminder_type', $type)
            ->whereDate('scheduled_date', $schedule->scheduled_date)
            ->exists();
    }

    protected function notifySchedule(VaccinationSchedule $schedule, string $type): bool
    {
        $user = $schedule->cattle?->farmer?->user;
        if (! $user) {
            return false;
        }

        $label = $type === 'h1' ? 'H-1' : 'Hari H';

        try {
            $user->notify(new VaccinationReminderNotification($schedule, $label, $type));
        } catch (Throwable $e) {
            report($e);
        }

        $title = $type === 'h1'
            ? 'Peringatan vaksin besok (H-1)'
            : 'Peringatan vaksin hari ini (Hari H)';
        $body = trim(($schedule->cattle?->code ?? '').' — '.($schedule->vaccine?->name ?? 'Vaksin'));
        $this->pushToUser($user, $title, $body);

        VaccineReminderLog::create([
            'vaccination_schedule_id' => $schedule->id,
            'reminder_type' => $type,
            'scheduled_date' => $schedule->scheduled_date,
            'sent_at' => now(),
        ]);

        return true;
    }

    public function pushToUser(User $user, string $title, string $body): void
    {
        $tokens = $user->deviceTokens()->pluck('token');
        if ($tokens->isEmpty()) {
            return;
        }

        $project = config('wowsapi.fcm.project_id');
        $credentials = config('wowsapi.fcm.credentials');

        if (! $project || ! $credentials || ! is_file($credentials)) {
            Log::info('FCM skipped (not configured)', ['user_id' => $user->id, 'title' => $title]);

            return;
        }

        foreach ($tokens as $token) {
            try {
                Http::timeout(10)->post('https://fcm.googleapis.com/v1/projects/'.$project.'/messages:send', [
                    'message' => [
                        'token' => $token,
                        'notification' => compact('title', 'body'),
                    ],
                ]);
            } catch (Throwable $e) {
                Log::warning('FCM send failed', ['error' => $e->getMessage()]);
            }
        }
    }

    public function register(User $user, array $data): DeviceToken
    {
        return DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $user->id,
                'platform' => $data['platform'],
                'device_name' => $data['device_name'] ?? null,
                'last_used_at' => now(),
            ],
        );
    }
}
