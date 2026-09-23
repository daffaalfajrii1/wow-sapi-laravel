<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use App\Models\VaccinationSchedule;
use App\Models\VaccineReminderLog;
use App\Notifications\VaccinationReminderNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotificationService
{
    public function sendVaccineReminders(?\DateTimeInterface $today = null): int
    {
        $today = \Illuminate\Support\Carbon::parse($today ?? now())->startOfDay();
        $sent = 0;

        $map = [
            'h7' => $today->copy()->addDays(7),
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
                if (VaccineReminderLog::query()
                    ->where('vaccination_schedule_id', $schedule->id)
                    ->where('reminder_type', $type)
                    ->whereDate('scheduled_date', $schedule->scheduled_date)
                    ->exists()) {
                    continue;
                }

                $user = $schedule->cattle?->farmer?->user;
                if (! $user) {
                    continue;
                }

                $label = match ($type) {
                    'h7' => 'H-7',
                    'h1' => 'H-1',
                    default => 'Hari H',
                };

                $user->notify(new VaccinationReminderNotification($schedule, $label));
                $this->pushToUser($user, 'Pengingat vaksin '.$label, ($schedule->cattle->code ?? '').' — '.($schedule->vaccine->name ?? 'Vaksin'));

                VaccineReminderLog::create([
                    'vaccination_schedule_id' => $schedule->id,
                    'reminder_type' => $type,
                    'scheduled_date' => $schedule->scheduled_date,
                    'sent_at' => now(),
                ]);
                $sent++;
            }
        }

        return $sent;
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
