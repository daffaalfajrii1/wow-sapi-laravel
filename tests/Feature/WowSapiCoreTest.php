<?php

namespace Tests\Feature;

use App\Models\AiExamination;
use App\Models\Cattle;
use App\Models\LoginOtp;
use App\Models\User;
use App\Models\VaccinationSchedule;
use App\Models\Vaccine;
use App\Models\WeightRecord;
use App\Notifications\PasswordResetOtpNotification;
use App\Notifications\RegistrationOtpNotification;
use App\Services\PushNotificationService;
use App\Services\WowSapiAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WowSapiCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_all_cattle(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $this->makeCattle($profile, $breed, ['code' => 'SAPI-0001']);
        [$other, $p2] = $this->makeFarmer();
        $this->makeCattle($p2, $breed, ['code' => 'SAPI-0002']);
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.cattle.index'))
            ->assertOk()
            ->assertSee('SAPI-0001')
            ->assertSee('SAPI-0002');
    }

    public function test_peternak_only_sees_own_cattle(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $this->makeCattle($profile, $breed, ['code' => 'SAPI-0001']);
        [, $p2] = $this->makeFarmer();
        $this->makeCattle($p2, $breed, ['code' => 'SAPI-0002']);

        $this->actingAs($farmer)->get(route('peternak.cattle.index'))
            ->assertOk()
            ->assertSee('SAPI-0001')
            ->assertDontSee('SAPI-0002');
    }

    public function test_peternak_cannot_access_other_cattle(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        [, $p2] = $this->makeFarmer();
        $other = $this->makeCattle($p2, $breed, ['code' => 'SAPI-0099']);

        $this->actingAs($farmer)->get(route('peternak.cattle.show', $other))->assertForbidden();

        $token = $farmer->createToken('t')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/cattle/'.$other->id)->assertForbidden();
    }

    public function test_breed_managed_only_by_admin(): void
    {
        [$farmer] = $this->makeFarmer();
        $admin = $this->makeAdmin();

        $this->actingAs($farmer)->post(route('admin.breeds.store'), ['name' => 'X'])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.breeds.store'), ['name' => 'Aceh'])->assertRedirect();
        $this->assertDatabaseHas('breeds', ['name' => 'Aceh']);
    }

    public function test_expired_register_otp_is_rejected_on_api(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        LoginOtp::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'channel' => 'api',
            'purpose' => 'register',
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp' => '123456',
        ])->assertStatus(422)->assertJsonFragment(['message' => 'Validasi gagal.']);
    }

    public function test_wrong_register_otp_is_rejected_on_api(): void
    {
        Notification::fake();
        $this->seedRoles();
        $this->postJson('/api/v1/auth/register', [
            'name' => 'API User',
            'email' => 'apiuser@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'apiuser@example.com',
            'otp' => '000000',
        ])->assertStatus(422);
    }

    public function test_sanctum_token_on_login_without_otp(): void
    {
        Notification::fake();
        $user = $this->makeFarmer()[0];

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('success', true)->assertJsonStructure(['data' => ['token']]);

        Notification::assertNothingSent();
    }

    public function test_api_register_requires_otp_before_token(): void
    {
        Notification::fake();
        $this->seedRoles();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'API User',
            'email' => 'apiuser@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated()->assertJsonMissing(['token']);

        $user = User::where('email', 'apiuser@example.com')->first();
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, RegistrationOtpNotification::class, function ($n) use ($user) {
            $this->postJson('/api/v1/auth/verify-otp', [
                'email' => $user->email,
                'otp' => $n->code,
            ])->assertOk()->assertJsonPath('success', true)->assertJsonStructure(['data' => ['token']]);

            return true;
        });

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_api_forgot_password_otp_resets_password(): void
    {
        Notification::fake();
        $user = $this->makeFarmer()[0];

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        Notification::assertSentTo($user, PasswordResetOtpNotification::class, function ($n) use ($user) {
            $this->postJson('/api/v1/auth/reset-password', [
                'email' => $user->email,
                'otp' => $n->code,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertOk();

            return true;
        });

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_fastapi_timeout_is_handled(): void
    {
        Storage::fake('public');
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->post('/api/v1/cattle/'.$cattle->id.'/ai/weight', [
            'image' => UploadedFile::fake()->image('sapi.jpg'),
        ])->assertStatus(504)->assertJsonFragment(['message' => 'Layanan AI tidak merespons. Silakan coba lagi beberapa saat.']);
    }

    public function test_no_cow_returns_indonesian_error(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response(['ok' => false, 'code' => 'NO_COW', 'message' => 'no cow'], 422),
        ]);
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->post('/api/v1/cattle/'.$cattle->id.'/ai/weight', [
            'image' => UploadedFile::fake()->image('sapi.jpg'),
        ])->assertStatus(422)->assertJsonFragment(['message' => 'Tidak ada sapi terdeteksi pada foto. Unggah foto yang menampilkan sapi dengan jelas.']);
    }

    public function test_weight_result_is_stored(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'cow_count' => 1,
                'detections' => [['confidence' => 0.93]],
                'estimated_weight_kg' => 412.5,
            ], 200),
        ]);
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->post('/api/v1/cattle/'.$cattle->id.'/ai/weight', [
            'image' => UploadedFile::fake()->image('sapi.jpg'),
        ])->assertOk()->assertJsonPath('data.estimated_weight_kg', 412.5);

        $this->assertDatabaseHas('ai_examinations', [
            'cattle_id' => $cattle->id,
            'type' => 'weight',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('weight_records', [
            'cattle_id' => $cattle->id,
            'source' => 'ai',
            'weight_kg' => 412.5,
        ]);
    }

    public function test_lumpy_result_is_stored(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'cow_count' => 1,
                'lumpy_positive' => true,
                'label' => 'Lumpy Skin',
                'probability' => 0.86,
                'detections' => [['confidence' => 0.8]],
            ], 200),
        ]);
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->post('/api/v1/cattle/'.$cattle->id.'/ai/lumpy', [
            'image' => UploadedFile::fake()->image('sapi.jpg'),
        ])->assertOk()->assertJsonPath('data.lumpy_label', 'Terindikasi Lumpy Skin');

        $this->assertDatabaseHas('ai_examinations', [
            'cattle_id' => $cattle->id,
            'type' => 'lumpy',
            'lumpy_detected' => 1,
        ]);
    }

    public function test_combined_partial_result_is_stored(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'cow_count' => 2,
                'detections' => [['confidence' => 0.8], ['confidence' => 0.7]],
                'weight' => null,
                'lumpy' => [
                    'lumpy_positive' => false,
                    'label' => 'Normal Skin',
                    'probability' => 0.91,
                ],
                'weight_skipped' => ['code' => 'MULTIPLE_COWS', 'message' => 'lebih dari 1'],
            ], 200),
        ]);
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->post('/api/v1/cattle/'.$cattle->id.'/ai/combined', [
            'image' => UploadedFile::fake()->image('sapi.jpg'),
        ])->assertOk()->assertJsonPath('data.status', 'partial');

        $this->assertDatabaseHas('ai_examinations', [
            'cattle_id' => $cattle->id,
            'type' => 'combined',
            'status' => 'partial',
        ]);
        $this->assertEquals(0, WeightRecord::where('cattle_id', $cattle->id)->count());
    }

    public function test_mortality_changes_cattle_status(): void
    {
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/cattle/'.$cattle->id.'/mortality', [
            'died_at' => now()->toDateTimeString(),
            'suspected_cause' => 'Sakit',
        ])->assertCreated();

        $this->assertEquals('dead', $cattle->fresh()->status);
        $this->assertNotNull($cattle->fresh()->mortality);
    }

    public function test_vaccine_complete_creates_record(): void
    {
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $vaccine = Vaccine::create(['name' => 'PMK', 'is_active' => true]);
        $schedule = VaccinationSchedule::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $vaccine->id,
            'scheduled_date' => now()->toDateString(),
            'status' => 'scheduled',
            'created_by' => $user->id,
        ]);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/vaccination-schedules/'.$schedule->id.'/complete', [
            'officer' => 'Dokter Hewan',
        ])->assertOk();

        $this->assertEquals('done', $schedule->fresh()->status);
        $this->assertDatabaseHas('vaccination_records', [
            'vaccination_schedule_id' => $schedule->id,
            'cattle_id' => $cattle->id,
        ]);
    }

    public function test_vaccine_reminder_is_not_duplicated(): void
    {
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $vaccine = Vaccine::create(['name' => 'LSD', 'is_active' => true]);
        VaccinationSchedule::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $vaccine->id,
            'scheduled_date' => now()->toDateString(),
            'status' => 'scheduled',
            'created_by' => $user->id,
        ]);

        $service = app(PushNotificationService::class);
        $this->assertEquals(1, $service->sendVaccineReminders());
        $this->assertEquals(0, $service->sendVaccineReminders());
        $this->assertDatabaseCount('vaccine_reminder_logs', 1);
    }
}
