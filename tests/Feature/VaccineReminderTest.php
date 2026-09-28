<?php

namespace Tests\Feature;

use App\Models\VaccinationSchedule;
use App\Models\Vaccine;
use App\Notifications\VaccinationReminderNotification;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VaccineReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_h1_and_h0_reminders(): void
    {
        Notification::fake();
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $vaccine = Vaccine::create(['name' => 'Anthrax', 'is_active' => true]);

        $today = VaccinationSchedule::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $vaccine->id,
            'scheduled_date' => now()->toDateString(),
            'status' => 'scheduled',
            'created_by' => $farmer->id,
        ]);
        $tomorrow = VaccinationSchedule::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $vaccine->id,
            'scheduled_date' => now()->addDay()->toDateString(),
            'status' => 'scheduled',
            'created_by' => $farmer->id,
        ]);
        VaccinationSchedule::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $vaccine->id,
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'status' => 'scheduled',
            'created_by' => $farmer->id,
        ]);

        $sent = app(PushNotificationService::class)->sendVaccineReminders();
        $this->assertSame(2, $sent);

        Notification::assertSentTo($farmer, VaccinationReminderNotification::class, fn ($n) => $n->whenType === 'h0' && $n->schedule->is($today));
        Notification::assertSentTo($farmer, VaccinationReminderNotification::class, fn ($n) => $n->whenType === 'h1' && $n->schedule->is($tomorrow));

        $again = app(PushNotificationService::class)->sendVaccineReminders();
        $this->assertSame(0, $again);
    }

    public function test_notifications_api_creates_and_lists_reminders(): void
    {
        Notification::fake();
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $vaccine = Vaccine::create(['name' => 'SE', 'is_active' => true]);
        VaccinationSchedule::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $vaccine->id,
            'scheduled_date' => now()->toDateString(),
            'status' => 'scheduled',
            'created_by' => $farmer->id,
        ]);

        $this->actingAs($farmer, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.vaccine_alerts.0.when_type', 'h0');

        Notification::assertSentTo($farmer, VaccinationReminderNotification::class);
    }
}
