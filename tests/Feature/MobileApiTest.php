<?php

namespace Tests\Feature;

use App\Models\AiExamination;
use App\Models\FeedRecord;
use App\Models\HealthRecord;
use App\Models\ReproductionRecord;
use App\Models\VaccinationRecord;
use App\Models\VaccinationSchedule;
use App\Models\Vaccine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_login_via_mobile_api(): void
    {
        $admin = $this->makeAdmin(['email' => 'admin-api@wowsapi.id']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertStatus(403)->assertJsonPath('success', false);
    }

    public function test_login_rejects_unknown_email_and_wrong_password(): void
    {
        [$farmer] = $this->makeFarmer(['email' => 'budi-login@wowsapi.id']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'tidakada@wowsapi.id',
            'password' => 'password',
        ])->assertStatus(422)->assertJsonPath('errors.email.0', 'Email tidak terdaftar.');

        $this->postJson('/api/v1/auth/login', [
            'email' => $farmer->email,
            'password' => 'salahsekali',
        ])->assertStatus(422)->assertJsonPath('errors.password.0', 'Kata sandi salah.');
    }

    public function test_peternak_can_change_password_from_profile(): void
    {
        [$farmer] = $this->makeFarmer(['email' => 'budi-pass@wowsapi.id']);
        $token = $farmer->createToken('flutter')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'salah',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.current_password.0', 'Kata sandi saat ini salah.');

        $this->withToken($token)
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'password',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('password-baru', $farmer->fresh()->password));

        $this->actingAs($farmer->fresh())
            ->put(route('peternak.profile.password'), [
                'current_password' => 'password-baru',
                'password' => 'password-lagi',
                'password_confirmation' => 'password-lagi',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Kata sandi diperbarui.');

        $this->assertTrue(Hash::check('password-lagi', $farmer->fresh()->password));
        $this->actingAs($farmer)->get(route('peternak.profile.show'))->assertOk()->assertSee('Ganti kata sandi');
    }

    public function test_peternak_dashboard_api(): void
    {
        [$farmer] = $this->makeFarmer();

        $token = $farmer->createToken('flutter')->plainTextToken;
        $this->withToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['kpis', 'weight_chart', 'activities', 'bcs_recommendations', 'herd']]);
    }

    public function test_dashboard_can_filter_weight_chart_by_cattle(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $one = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0201', 'name' => 'Gibbong']);
        $this->makeCattle($profile, $breed, ['code' => 'SAPI-0202', 'name' => 'Limo']);
        $token = $farmer->createToken('flutter')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/dashboard?cattle_id='.$one->id)
            ->assertOk()
            ->assertJsonPath('data.chart_cattle_id', $one->id)
            ->assertJsonPath('data.herd.0.code', 'SAPI-0201');
    }

    public function test_peternak_can_delete_own_cattle(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0301']);
        $token = $farmer->createToken('flutter')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/v1/cattle/'.$cattle->id)
            ->assertOk()
            ->assertJsonPath('message', 'Data sapi dihapus.');

        $this->assertSoftDeleted('cattle', ['id' => $cattle->id]);
    }

    public function test_peternak_can_delete_cattle_from_web(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0302']);

        $this->actingAs($farmer)
            ->delete(route('peternak.cattle.destroy', $cattle))
            ->assertRedirect(route('peternak.cattle.index'));

        $this->assertSoftDeleted('cattle', ['id' => $cattle->id]);
    }

    public function test_peternak_can_update_and_delete_feed_reproduction_and_vaccine(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0401']);
        $vaccine = Vaccine::create(['name' => 'PMK', 'is_active' => true]);
        $token = $farmer->createToken('flutter')->plainTextToken;

        $feed = FeedRecord::create([
            'cattle_id' => $cattle->id,
            'feed_name' => 'Rumput',
            'quantity' => 10,
            'unit' => 'kg',
            'cost' => 25000,
            'fed_at' => now(),
            'created_by' => $farmer->id,
        ]);
        $repro = ReproductionRecord::create([
            'cattle_id' => $cattle->id,
            'type' => 'heat',
            'event_date' => now()->toDateString(),
            'created_by' => $farmer->id,
        ]);
        $schedule = VaccinationSchedule::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $vaccine->id,
            'scheduled_date' => now()->toDateString(),
            'status' => 'scheduled',
            'created_by' => $farmer->id,
        ]);
        $record = VaccinationRecord::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $vaccine->id,
            'administered_at' => now(),
            'created_by' => $farmer->id,
        ]);

        $this->withToken($token)
            ->putJson('/api/v1/feeds/'.$feed->id, [
                'feed_name' => 'Konsentrat',
                'quantity' => 8,
                'unit' => 'kg',
                'cost' => 40000,
                'fed_at' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.feed_name', 'Konsentrat');

        $this->withToken($token)
            ->putJson('/api/v1/reproduction/'.$repro->id, [
                'type' => 'pregnant',
                'event_date' => now()->toDateString(),
                'partner_code' => 'PJ-01',
            ])
            ->assertOk()
            ->assertJsonPath('data.type', 'pregnant');

        $this->withToken($token)
            ->putJson('/api/v1/vaccination-schedules/'.$schedule->id, [
                'vaccine_id' => $vaccine->id,
                'scheduled_date' => now()->addDay()->toDateString(),
            ])
            ->assertOk();

        $this->withToken($token)
            ->putJson('/api/v1/vaccination-records/'.$record->id, [
                'vaccine_id' => $vaccine->id,
                'administered_at' => now()->toDateTimeString(),
                'notes' => 'Dosis ulang',
            ])
            ->assertOk();

        $this->withToken($token)->deleteJson('/api/v1/feeds/'.$feed->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/v1/reproduction/'.$repro->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/v1/vaccination-schedules/'.$schedule->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/v1/vaccination-records/'.$record->id)->assertOk();

        $this->assertDatabaseMissing('feed_records', ['id' => $feed->id]);
        $this->assertDatabaseMissing('reproduction_records', ['id' => $repro->id]);
        $this->assertDatabaseMissing('vaccination_schedules', ['id' => $schedule->id]);
        $this->assertDatabaseMissing('vaccination_records', ['id' => $record->id]);
    }

    public function test_peternak_cannot_edit_other_farmer_records(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        [, $otherProfile] = $this->makeFarmer();
        $other = $this->makeCattle($otherProfile, $breed, ['code' => 'SAPI-0499']);
        $feed = FeedRecord::create([
            'cattle_id' => $other->id,
            'feed_name' => 'Rumput',
            'fed_at' => now(),
            'created_by' => $other->farmer?->user_id,
        ]);
        $token = $farmer->createToken('flutter')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/feeds/'.$feed->id, ['feed_name' => 'Dicuri'])
            ->assertForbidden();
        $this->withToken($token)
            ->deleteJson('/api/v1/feeds/'.$feed->id)
            ->assertForbidden();
    }

    public function test_peternak_can_edit_and_delete_records_from_web(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0402']);
        $vaccine = Vaccine::create(['name' => 'LSD', 'is_active' => true]);
        $feed = FeedRecord::create([
            'cattle_id' => $cattle->id,
            'feed_name' => 'Rumput',
            'quantity' => 5,
            'unit' => 'kg',
            'fed_at' => now(),
            'created_by' => $farmer->id,
        ]);
        $repro = ReproductionRecord::create([
            'cattle_id' => $cattle->id,
            'type' => 'mating',
            'event_date' => now()->toDateString(),
            'created_by' => $farmer->id,
        ]);
        $schedule = VaccinationSchedule::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $vaccine->id,
            'scheduled_date' => now()->toDateString(),
            'status' => 'scheduled',
            'created_by' => $farmer->id,
        ]);

        $this->actingAs($farmer)
            ->get(route('peternak.cattle.show', [$cattle, 'tab' => 'pakan', 'feed' => $feed->id]))
            ->assertOk()
            ->assertSee('Rumput')
            ->assertSee('Simpan perubahan');

        $this->actingAs($farmer)
            ->put(route('peternak.cattle.feeds.update', $feed), [
                'feed_name' => 'Silase',
                'quantity' => 7,
                'unit' => 'kg',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('feed_records', ['id' => $feed->id, 'feed_name' => 'Silase']);

        $this->actingAs($farmer)
            ->put(route('peternak.cattle.reproduction.update', $repro), [
                'type' => 'birth',
                'event_date' => now()->toDateString(),
                'calf_count' => 1,
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('reproduction_records', ['id' => $repro->id, 'type' => 'birth']);

        $this->actingAs($farmer)
            ->put(route('peternak.cattle.schedules.update', $schedule), [
                'vaccine_id' => $vaccine->id,
                'scheduled_date' => now()->addDays(2)->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($farmer)->get(route('peternak.feeds.index'))->assertOk()->assertSee('Ubah')->assertSee('Hapus');
        $this->actingAs($farmer)->get(route('peternak.reproduction.index'))->assertOk()->assertSee('Ubah')->assertSee('Hapus');
        $this->actingAs($farmer)->get(route('peternak.vaccinations.index'))->assertOk()->assertSee('Ubah')->assertSee('Hapus');

        $this->actingAs($farmer)->delete(route('peternak.cattle.feeds.destroy', $feed))->assertRedirect();
        $this->actingAs($farmer)->delete(route('peternak.cattle.reproduction.destroy', $repro))->assertRedirect();
        $this->actingAs($farmer)->delete(route('peternak.cattle.schedules.destroy', $schedule))->assertRedirect();

        $this->assertDatabaseMissing('feed_records', ['id' => $feed->id]);
        $this->assertDatabaseMissing('reproduction_records', ['id' => $repro->id]);
        $this->assertDatabaseMissing('vaccination_schedules', ['id' => $schedule->id]);
    }

    public function test_peternak_can_update_delete_health_and_save_lumpy_exam(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0501']);
        $token = $farmer->createToken('flutter')->plainTextToken;
        $health = HealthRecord::create([
            'cattle_id' => $cattle->id,
            'type' => 'lumpy',
            'title' => 'Indikasi lama',
            'description' => 'Catatan awal',
            'occurred_at' => now(),
            'created_by' => $farmer->id,
        ]);
        $exam = AiExamination::create([
            'cattle_id' => $cattle->id,
            'user_id' => $farmer->id,
            'type' => 'lumpy',
            'image_path' => 'ai-examinations/demo.jpg',
            'lumpy_detected' => true,
            'lumpy_label' => 'Terindikasi Lumpy Skin',
            'status' => 'success',
            'examined_at' => now(),
        ]);

        $this->withToken($token)
            ->putJson('/api/v1/health/'.$health->id, [
                'type' => 'penyakit',
                'title' => 'Demam',
                'medicine' => 'Antibiotik',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Demam');

        $this->withToken($token)
            ->postJson('/api/v1/cattle/'.$cattle->id.'/ai/health', [
                'examination_id' => $exam->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'lumpy');

        $this->assertDatabaseHas('health_records', [
            'cattle_id' => $cattle->id,
            'title' => 'Terindikasi Lumpy Skin',
        ]);

        $this->withToken($token)->deleteJson('/api/v1/health/'.$health->id)->assertOk();
        $this->assertDatabaseMissing('health_records', ['id' => $health->id]);

        $this->withToken($token)->deleteJson('/api/v1/ai-examinations/'.$exam->id)->assertOk();
        $this->assertDatabaseMissing('ai_examinations', ['id' => $exam->id]);
    }

    public function test_peternak_can_update_health_from_ai_photo(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'cow_count' => 1,
                'lumpy_positive' => false,
                'label' => 'Normal Skin',
                'probability' => 0.91,
                'detections' => [['confidence' => 0.8]],
            ], 200),
        ]);
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0502']);
        $health = HealthRecord::create([
            'cattle_id' => $cattle->id,
            'type' => 'lumpy',
            'title' => 'Indikasi lama',
            'occurred_at' => now(),
            'created_by' => $farmer->id,
        ]);
        $token = $farmer->createToken('flutter')->plainTextToken;

        $this->withToken($token)
            ->post('/api/v1/health/'.$health->id.'/ai', [
                'image' => UploadedFile::fake()->image('sapi.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('data.lumpy_label', 'Tidak Terindikasi Lumpy Skin');

        $this->assertDatabaseHas('health_records', [
            'id' => $health->id,
            'title' => 'Tidak Terindikasi Lumpy Skin',
            'status' => 'sehat',
        ]);
    }

    public function test_peternak_can_edit_and_delete_health_from_web(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0503']);
        $health = HealthRecord::create([
            'cattle_id' => $cattle->id,
            'type' => 'cedera',
            'title' => 'Luka kaki',
            'occurred_at' => now(),
            'created_by' => $farmer->id,
        ]);
        $exam = AiExamination::create([
            'cattle_id' => $cattle->id,
            'user_id' => $farmer->id,
            'type' => 'lumpy',
            'image_path' => 'ai-examinations/web.jpg',
            'lumpy_detected' => true,
            'lumpy_label' => 'Terindikasi Lumpy Skin',
            'status' => 'success',
            'examined_at' => now(),
        ]);

        $this->actingAs($farmer)
            ->get(route('peternak.cattle.show', [$cattle, 'tab' => 'kesehatan', 'health' => $health->id]))
            ->assertOk()
            ->assertSee('Luka kaki')
            ->assertSee('Simpan perubahan')
            ->assertSee('Atau perbarui dengan foto AI');

        $this->actingAs($farmer)
            ->put(route('peternak.cattle.health.update', $health), [
                'type' => 'penyakit',
                'title' => 'Mata meradang',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('health_records', ['id' => $health->id, 'title' => 'Mata meradang']);

        $this->actingAs($farmer)->get(route('peternak.health.index'))->assertOk()->assertSee('Ubah')->assertSee('Hapus');
        $this->actingAs($farmer)->get(route('peternak.cattle.show', [$cattle, 'tab' => 'ai']))->assertOk()->assertSee('Hapus');

        $this->actingAs($farmer)->delete(route('peternak.cattle.health.destroy', $health))->assertRedirect();
        $this->actingAs($farmer)->delete(route('peternak.cattle.ai.destroy', $exam))->assertRedirect();
        $this->assertDatabaseMissing('health_records', ['id' => $health->id]);
        $this->assertDatabaseMissing('ai_examinations', ['id' => $exam->id]);
    }
}
