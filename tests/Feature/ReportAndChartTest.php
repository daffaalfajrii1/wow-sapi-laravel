<?php

namespace Tests\Feature;

use App\Models\AiExamination;
use App\Models\FeedRecord;
use App\Models\WeightRecord;
use App\Services\CattleTimelineService;
use App\Support\DatePeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportAndChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_peternak_dashboard_accepts_weight_range(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0100']);
        WeightRecord::create([
            'cattle_id' => $cattle->id,
            'source' => 'manual',
            'weight_kg' => 210,
            'measured_at' => now()->subDays(10),
            'created_by' => $farmer->id,
        ]);
        WeightRecord::create([
            'cattle_id' => $cattle->id,
            'source' => 'manual',
            'weight_kg' => 400,
            'measured_at' => now()->subMonths(5),
            'created_by' => $farmer->id,
        ]);

        $this->actingAs($farmer)
            ->get(route('peternak.dashboard', ['range' => '1m']))
            ->assertOk()
            ->assertSee('Perkembangan Bobot')
            ->assertSee('1 Bulan')
            ->assertSee('Semua sapi')
            ->assertSee('SAPI-0100');
    }

    public function test_laporan_range_filters_timeline_and_keeps_unfiltered_default(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0101']);
        WeightRecord::create([
            'cattle_id' => $cattle->id,
            'source' => 'manual',
            'weight_kg' => 200,
            'measured_at' => Carbon::parse('2026-01-15'),
            'created_by' => $farmer->id,
        ]);
        WeightRecord::create([
            'cattle_id' => $cattle->id,
            'source' => 'manual',
            'weight_kg' => 250,
            'measured_at' => Carbon::parse('2026-08-15'),
            'created_by' => $farmer->id,
        ]);
        FeedRecord::create([
            'cattle_id' => $cattle->id,
            'feed_name' => 'Hijauan',
            'quantity' => 10,
            'unit' => 'kg',
            'cost' => 150000,
            'fed_at' => Carbon::parse('2026-08-20'),
            'created_by' => $farmer->id,
        ]);

        $this->actingAs($farmer)
            ->get(route('peternak.reports.index', ['cattle_id' => $cattle->id]))
            ->assertOk()
            ->assertSee('200,0 kg')
            ->assertSee('250,0 kg')
            ->assertSee('Rp 150.000');

        $this->actingAs($farmer)
            ->get(route('peternak.reports.index', [
                'cattle_id' => $cattle->id,
                'range' => 'custom',
                'from' => '2026-08-01',
                'to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertSee('250,0 kg')
            ->assertDontSee('200,0 kg');
    }

    public function test_peternak_can_download_own_cattle_pdf(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0102']);
        WeightRecord::create([
            'cattle_id' => $cattle->id,
            'source' => 'manual',
            'weight_kg' => 216.7,
            'measured_at' => now(),
            'created_by' => $farmer->id,
        ]);

        $this->actingAs($farmer)
            ->get(route('peternak.reports.pdf', $cattle))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_peternak_cannot_download_other_cattle_pdf(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        [, $otherProfile] = $this->makeFarmer();
        $other = $this->makeCattle($otherProfile, $breed, ['code' => 'SAPI-0999']);

        $this->actingAs($farmer)
            ->get(route('peternak.reports.pdf', $other))
            ->assertForbidden();
    }

    public function test_laporan_and_pdf_omit_failed_ai_examinations(): void
    {
        [$farmer, $profile, $breed] = $this->makeFarmer();
        $cattle = $this->makeCattle($profile, $breed, ['code' => 'SAPI-0103']);
        AiExamination::create([
            'cattle_id' => $cattle->id,
            'user_id' => $farmer->id,
            'type' => 'lumpy',
            'image_path' => 'ai-examinations/ok.jpg',
            'lumpy_detected' => true,
            'lumpy_label' => 'Terindikasi Lumpy Skin',
            'status' => 'success',
            'examined_at' => now(),
        ]);
        AiExamination::create([
            'cattle_id' => $cattle->id,
            'user_id' => $farmer->id,
            'type' => 'lumpy',
            'image_path' => 'ai-examinations/fail.jpg',
            'status' => 'failed',
            'examined_at' => now()->subHour(),
        ]);

        $full = app(CattleTimelineService::class)->report($cattle);
        $this->assertTrue($full['ai']->contains(fn ($row) => $row->status === 'failed'));

        $laporan = app(CattleTimelineService::class)->report($cattle, excludeFailed: true);
        $this->assertFalse($laporan['ai']->contains(fn ($row) => $row->status === 'failed'));
        $this->assertTrue($laporan['ai']->contains(fn ($row) => $row->status === 'success'));
        $this->assertFalse($laporan['timeline']->contains(fn ($item) => str_contains((string) $item['title'], 'Gagal')));

        $token = $farmer->createToken('flutter')->plainTextToken;
        $this->withToken($token)
            ->getJson('/api/v1/cattle/'.$cattle->id.'/report')
            ->assertOk()
            ->assertJsonMissing(['title' => 'AI Pemeriksaan Kesehatan (Gagal)']);

        $this->actingAs($farmer)
            ->get(route('peternak.reports.index', ['cattle_id' => $cattle->id]))
            ->assertOk()
            ->assertSee('Terindikasi Lumpy Skin')
            ->assertDontSee('AI Pemeriksaan Kesehatan (Gagal)');

        $html = view('pdf.cattle-report', [
            'cattle' => $cattle->fresh()->load(['farmer.user', 'breed', 'latestWeight', 'latestBcs', 'latestLumpy']),
            'report' => $laporan,
            'period' => null,
            'generatedAt' => now(),
        ])->render();
        $this->assertStringContainsString('Terindikasi Lumpy Skin', $html);
        $this->assertStringNotContainsString('AI Pemeriksaan Kesehatan (Gagal)', $html);
    }

    public function test_date_period_defaults_six_months_and_all(): void
    {
        $six = DatePeriod::fromRequest(request()->replace(['range' => '6m']), '6m');
        $this->assertSame('6m', $six->range);
        $this->assertNotNull($six->from);
        $this->assertTrue($six->from->gte(now()->subMonths(6)->subDay()));

        $all = DatePeriod::fromRequest(request()->replace(['range' => 'all']), 'all', true);
        $this->assertSame('all', $all->range);
        $this->assertNull($all->from);
        $this->assertNull($all->to);
    }
}
