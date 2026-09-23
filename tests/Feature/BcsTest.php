<?php

namespace Tests\Feature;

use App\Models\BcsRecord;
use App\Models\Cattle;
use App\Models\WeightRecord;
use App\Services\BcsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BcsTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_mapping(): void
    {
        $this->assertSame('Sangat Kurus', BcsService::category(1.0));
        $this->assertSame('Sangat Kurus', BcsService::category(1.5));
        $this->assertSame('Kurus', BcsService::category(2.0));
        $this->assertSame('Ideal', BcsService::category(2.5));
        $this->assertSame('Ideal', BcsService::category(3.5));
        $this->assertSame('Gemuk', BcsService::category(4.0));
        $this->assertSame('Sangat Gemuk', BcsService::category(4.5));
        $this->assertSame('Sangat Gemuk', BcsService::category(5.0));
    }

    public function test_score_zero_is_rejected(): void
    {
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/cattle/'.$cattle->id.'/bcs', [
            'score' => 0,
        ])->assertStatus(422);
    }

    public function test_score_six_is_rejected(): void
    {
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/cattle/'.$cattle->id.'/bcs', [
            'score' => 6,
        ])->assertStatus(422);
    }

    public function test_non_half_step_score_is_rejected(): void
    {
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/cattle/'.$cattle->id.'/bcs', [
            'score' => 2.3,
        ])->assertStatus(422);
    }

    public function test_peternak_cannot_add_bcs_for_other_cattle(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        [, $p2] = $this->makeFarmer();
        $other = $this->makeCattle($p2, $breed, ['code' => 'SAPI-0099']);
        $token = $farmer->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/cattle/'.$other->id.'/bcs', [
            'score' => 2.5,
        ])->assertForbidden();
    }

    public function test_weight_snapshot_and_recommendation_are_stored(): void
    {
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        WeightRecord::create([
            'cattle_id' => $cattle->id,
            'source' => 'ai',
            'weight_kg' => 216.7,
            'measured_at' => now()->subDay(),
            'created_by' => $user->id,
        ]);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/cattle/'.$cattle->id.'/bcs', [
            'score' => 2.5,
            'assessed_at' => '2026-09-21',
            'notes' => 'Kondisi tubuh membaik.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Penilaian kondisi tubuh berhasil disimpan.')
            ->assertJsonPath('data.score', 2.5)
            ->assertJsonPath('data.category', 'Ideal')
            ->assertJsonPath('data.weight_kg', 216.7)
            ->assertJsonPath('data.recommendation.summary', 'Kondisi tubuh sapi berada pada kisaran ideal.');

        $row = BcsRecord::first();
        $this->assertEquals(216.7, (float) $row->weight_kg_snapshot);
        $this->assertNotEmpty($row->recommendation_summary);
        $this->assertIsArray($row->recommendations);

        WeightRecord::create([
            'cattle_id' => $cattle->id,
            'source' => 'manual',
            'weight_kg' => 240,
            'measured_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->assertEquals(216.7, (float) $row->fresh()->weight_kg_snapshot);
        $this->assertSame('Kondisi tubuh sapi berada pada kisaran ideal.', $row->fresh()->recommendation_summary);
    }

    public function test_history_is_newest_first(): void
    {
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/cattle/'.$cattle->id.'/bcs', [
            'score' => 2.0,
            'assessed_at' => '2026-09-05',
        ])->assertCreated();
        $this->withToken($token)->postJson('/api/v1/cattle/'.$cattle->id.'/bcs', [
            'score' => 2.5,
            'assessed_at' => '2026-09-21',
        ])->assertCreated();

        $json = $this->withToken($token)->getJson('/api/v1/cattle/'.$cattle->id.'/bcs')
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(2.5, (float) $json[0]['score'], 0.01);
        $this->assertEqualsWithDelta(2.0, (float) $json[1]['score'], 0.01);
    }

    public function test_web_store_and_indonesian_date_on_show(): void
    {
        [$user, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed);

        $this->actingAs($user)->post(route('peternak.cattle.bcs.store', $cattle), [
            'score' => 2.0,
            'assessed_at' => '2026-09-21',
        ])->assertRedirect();

        $this->actingAs($user)->get(route('peternak.cattle.show', [$cattle, 'tab' => 'bcs']))
            ->assertOk()
            ->assertSee('Penilaian Kondisi Tubuh (BCS)')
            ->assertSee('Kurus')
            ->assertSee('21 September 2026');
    }
}
