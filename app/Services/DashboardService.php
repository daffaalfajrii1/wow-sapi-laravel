<?php

namespace App\Services;

use App\Models\AiExamination;
use App\Models\Cattle;
use App\Models\FarmerProfile;
use App\Models\HealthRecord;
use App\Models\User;
use App\Models\VaccinationSchedule;
use App\Models\WeightRecord;
use App\Support\DatePeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function admin(): array
    {
        $today = now()->startOfDay();

        $totalCattle = Cattle::count();
        $cattleLastMonth = Cattle::where('created_at', '<', now()->startOfMonth())->count();
        $activeFarmers = FarmerProfile::whereHas('cattle')->count();
        $farmersLastMonth = FarmerProfile::where('created_at', '<', now()->startOfMonth())->whereHas('cattle')->count();
        $scansToday = AiExamination::whereDate('examined_at', $today)->count();
        $scansYesterday = AiExamination::whereDate('examined_at', $today->copy()->subDay())->count();
        $healthCases = HealthRecord::where('created_at', '>=', now()->subWeek())
            ->where(function ($q) {
                $q->where('status', '!=', 'sehat')->orWhereNull('status');
            })
            ->count();
        $healthPrev = HealthRecord::whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])
            ->where(function ($q) {
                $q->where('status', '!=', 'sehat')->orWhereNull('status');
            })
            ->count();

        $months = collect(range(5, 0))->map(function ($i) {
            $d = now()->subMonths($i);

            return [
                'key' => $d->format('Y-m'),
                'label' => $d->translatedFormat('M'),
                'avg' => round((float) WeightRecord::whereYear('measured_at', $d->year)
                    ->whereMonth('measured_at', $d->month)
                    ->avg('weight_kg'), 1),
            ];
        });

        $lumpy = AiExamination::query()
            ->whereNotNull('lumpy_detected')
            ->get();

        $sehat = $lumpy->where('lumpy_detected', false)->count();
        $suspek = $lumpy->filter(fn ($e) => $e->lumpy_detected && $e->lumpy_label === 'Perlu Pemeriksaan Lanjutan')->count();
        $positif = $lumpy->filter(fn ($e) => $e->lumpy_detected && $e->lumpy_label !== 'Perlu Pemeriksaan Lanjutan')->count();
        $lumpyTotal = max($sehat + $suspek + $positif, 1);

        return [
            'kpis' => [
                'total_sapi' => $totalCattle,
                'total_sapi_delta' => $totalCattle - $cattleLastMonth,
                'peternak_aktif' => $activeFarmers,
                'peternak_delta' => $activeFarmers - $farmersLastMonth,
                'scan_hari_ini' => $scansToday,
                'scan_delta_pct' => $scansYesterday > 0 ? round((($scansToday - $scansYesterday) / $scansYesterday) * 100) : ($scansToday ? 100 : 0),
                'kasus_kesehatan' => $healthCases,
                'kasus_delta_pct' => $healthPrev > 0 ? round((($healthCases - $healthPrev) / $healthPrev) * 100) : 0,
            ],
            'weight_chart' => $months,
            'lumpy' => [
                'sehat' => $sehat,
                'suspek' => $suspek,
                'positif' => $positif,
                'pct_sehat' => round(($sehat / $lumpyTotal) * 100),
            ],
            'latest_cattle' => Cattle::with(['farmer.user', 'latestWeight', 'latestLumpy'])->latest()->limit(5)->get(),
            'schedules' => VaccinationSchedule::with(['cattle', 'vaccine'])
                ->where('status', 'scheduled')
                ->whereDate('scheduled_date', '>=', now())
                ->orderBy('scheduled_date')
                ->limit(6)
                ->get(),
            'ai_online' => app(WowSapiAiService::class)->health()['reachable'] ?? false,
        ];
    }

    public function farmer(User $user, ?DatePeriod $period = null, ?int $cattleId = null): array
    {
        $profile = $user->farmerProfile;
        $ids = $profile ? Cattle::where('farmer_id', $profile->id)->pluck('id') : collect();
        $period ??= new DatePeriod('6m', now()->subMonths(6)->startOfDay(), now()->endOfDay());
        $chartCattleId = $cattleId && $ids->contains(fn ($id) => (int) $id === (int) $cattleId) ? (int) $cattleId : null;
        $chartIds = $chartCattleId ? collect([$chartCattleId]) : $ids;

        return [
            'total' => $ids->count(),
            'aktif' => Cattle::whereIn('id', $ids)->where('status', 'active')->count(),
            'vaksin_terdekat' => VaccinationSchedule::with(['cattle', 'vaccine'])
                ->whereIn('cattle_id', $ids)
                ->where('status', 'scheduled')
                ->whereDate('scheduled_date', '>=', now())
                ->orderBy('scheduled_date')
                ->first(),
            'ai_bulan_ini' => AiExamination::whereIn('cattle_id', $ids)->whereMonth('examined_at', now()->month)->count(),
            'perlu_perhatian' => Cattle::with('latestLumpy')->whereIn('id', $ids)->get()
                ->filter(fn ($c) => ($c->healthBadge()['key'] ?? 'sehat') !== 'sehat')
                ->count(),
            'latest_cattle' => Cattle::with(['breed', 'latestWeight', 'latestLumpy'])->whereIn('id', $ids)->latest()->limit(5)->get(),
            'assessed_cattle' => Cattle::with(['latestBcs', 'latestWeight'])
                ->whereIn('id', $ids)
                ->whereHas('latestBcs')
                ->get()
                ->sortByDesc(fn (Cattle $c) => $c->latestBcs?->assessed_at)
                ->values(),
            'weight_chart' => $this->weightChart($chartIds, $period),
            'period' => $period,
            'herd' => Cattle::whereIn('id', $ids)->orderBy('code')->get(['id', 'code', 'name', 'main_photo']),
            'chart_cattle_id' => $chartCattleId,
            'schedules' => VaccinationSchedule::with(['cattle', 'vaccine'])
                ->whereIn('cattle_id', $ids)
                ->where('status', 'scheduled')
                ->orderBy('scheduled_date')
                ->limit(6)
                ->get(),
        ];
    }

    public function weightChart(iterable $cattleIds, DatePeriod $period): Collection
    {
        $from = $period->from ?? now()->subMonths(6)->startOfDay();
        $to = $period->to ?? now()->endOfDay();
        $days = $from->diffInDays($to);

        $rows = WeightRecord::query()
            ->whereIn('cattle_id', $cattleIds)
            ->whereBetween('measured_at', [$from, $to])
            ->orderBy('measured_at')
            ->get();

        if ($days <= 45) {
            return $rows
                ->groupBy(fn (WeightRecord $w) => $w->measured_at->format('Y-m-d'))
                ->map(fn ($group, $key) => [
                    'label' => Carbon::parse($key)->locale('id')->translatedFormat('d M'),
                    'avg' => round((float) $group->avg('weight_kg'), 1),
                ])
                ->values();
        }

        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();
        $out = collect();
        while ($cursor->lte($end)) {
            $avg = $rows->filter(fn (WeightRecord $w) => $w->measured_at->isSameMonth($cursor))->avg('weight_kg');
            $out->push([
                'label' => $cursor->locale('id')->translatedFormat('M Y'),
                'avg' => $avg !== null ? round((float) $avg, 1) : null,
            ]);
            $cursor->addMonth();
        }

        return $out;
    }
}
