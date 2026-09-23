<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CattleResource;
use App\Models\AiExamination;
use App\Models\Cattle;
use App\Models\VaccinationSchedule;
use App\Services\CattleReportPdfService;
use App\Services\CattleTimelineService;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use App\Support\DatePeriod;
use Illuminate\Http\Request;

class FarmerApiController extends Controller
{
    public function dashboard(Request $request, DashboardService $dashboard)
    {
        $this->ensureFarmer($request);
        $period = DatePeriod::fromRequest($request, '6m');
        $cattleId = $request->integer('cattle_id') ?: null;
        $data = $dashboard->farmer($request->user(), $period, $cattleId);
        $ids = Cattle::ownedBy($request->user())->pluck('id');

        $herd = Cattle::with('latestLumpy')->whereIn('id', $ids)->get();
        $sehat = $herd->filter(fn (Cattle $c) => ($c->healthBadge()['key'] ?? 'sehat') === 'sehat')->count();
        $aiToday = AiExamination::whereIn('cattle_id', $ids)->whereDate('examined_at', now())->count();
        $baruBulanIni = Cattle::whereIn('id', $ids)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        $aiActs = AiExamination::with('cattle')
            ->whereIn('cattle_id', $ids)
            ->latest('examined_at')
            ->limit(8)
            ->get()
            ->map(fn (AiExamination $e) => [
                'kind' => 'ai',
                'title' => $e->timelineTitle(),
                'cattle_id' => $e->cattle_id,
                'cattle_code' => $e->cattle?->code,
                'cattle_name' => $e->cattle?->name,
                'cattle_photo_url' => $e->cattle?->photoUrl(),
                'status' => $e->statusLabel(),
                'at' => optional($e->examined_at)?->toIso8601String(),
            ]);

        $vaxActs = VaccinationSchedule::with(['cattle', 'vaccine'])
            ->whereIn('cattle_id', $ids)
            ->orderByDesc('scheduled_date')
            ->limit(6)
            ->get()
            ->map(fn (VaccinationSchedule $s) => [
                'kind' => 'vaksin',
                'title' => 'Jadwal vaksin '.($s->vaccine?->name ?? ''),
                'cattle_id' => $s->cattle_id,
                'cattle_code' => $s->cattle?->code,
                'cattle_name' => $s->cattle?->name,
                'cattle_photo_url' => $s->cattle?->photoUrl(),
                'status' => $s->statusLabel(),
                'at' => optional($s->scheduled_date)?->toIso8601String(),
            ]);

        $activities = $aiActs->concat($vaxActs)
            ->sortByDesc('at')
            ->take(8)
            ->values();

        return ApiResponse::success([
            'user_name' => $request->user()->name,
            'kpis' => [
                'total_sapi' => $data['total'],
                'sehat' => $sehat,
                'sehat_pct' => $data['total'] > 0 ? (int) round(($sehat / $data['total']) * 100) : 0,
                'ai_hari_ini' => $aiToday,
                'ai_bulan_ini' => $data['ai_bulan_ini'],
                'perlu_perhatian' => $data['perlu_perhatian'],
                'baru_bulan_ini' => $baruBulanIni,
            ],
            'weight_chart' => $data['weight_chart'],
            'chart_cattle_id' => $data['chart_cattle_id'] ?? null,
            'herd' => ($data['herd'] ?? collect())->map(fn (Cattle $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
            ])->values(),
            'period' => [
                'range' => $period->range,
                'label' => $period->label(),
                'from' => $period->from?->toDateString(),
                'to' => $period->to?->toDateString(),
            ],
            'activities' => $activities,
            'bcs_recommendations' => ($data['assessed_cattle'] ?? collect())->map(fn (Cattle $c) => [
                'cattle_id' => $c->id,
                'cattle_code' => $c->code,
                'cattle_name' => $c->name,
                'cattle_photo_url' => $c->photoUrl(),
                'score' => $c->latestBcs?->score,
                'category' => $c->latestBcs?->category,
                'weight_kg' => $c->latestBcs?->weight_kg_snapshot,
                'assessed_at' => optional($c->latestBcs?->assessed_at)?->toIso8601String(),
                'recommendation_summary' => $c->latestBcs?->recommendation_summary,
                'recommendations' => $c->latestBcs?->recommendations ?? [],
            ])->values(),
            'schedules' => $data['schedules']->map(fn (VaccinationSchedule $s) => [
                'id' => $s->id,
                'cattle_id' => $s->cattle_id,
                'cattle_code' => $s->cattle?->code,
                'vaccine' => $s->vaccine?->name,
                'scheduled_date' => optional($s->scheduled_date)?->toDateString(),
                'status' => $s->status,
                'status_label' => $s->statusLabel(),
            ]),
        ]);
    }

    public function notifications(Request $request)
    {
        $items = $request->user()->notifications()->paginate(20);

        return ApiResponse::success([
            'unread' => $request->user()->unreadNotifications()->count(),
            'items' => $items->through(fn ($n) => [
                'id' => $n->id,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function markNotifications(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::success(null, 'Semua notifikasi ditandai dibaca.');
    }

    public function examinations(Request $request)
    {
        $this->ensureFarmer($request);
        $ids = Cattle::ownedBy($request->user())->pluck('id');
        $items = AiExamination::with('cattle')
            ->whereIn('cattle_id', $ids)
            ->latest('examined_at')
            ->paginate(20);

        return ApiResponse::success($items->through(fn (AiExamination $e) => [
            'id' => $e->id,
            'cattle_id' => $e->cattle_id,
            'cattle_code' => $e->cattle?->code,
            'type' => $e->type,
            'type_label' => $e->typeLabel(),
            'status' => $e->status,
            'estimated_weight_kg' => $e->estimated_weight_kg,
            'lumpy_label' => $e->lumpy_label,
            'image_url' => $e->imageUrl(),
            'examined_at' => $e->examined_at?->toIso8601String(),
        ]));
    }

    public function vaccinations(Request $request)
    {
        $this->ensureFarmer($request);
        $ids = Cattle::ownedBy($request->user())->pluck('id');
        $items = VaccinationSchedule::with(['cattle', 'vaccine'])
            ->whereIn('cattle_id', $ids)
            ->orderBy('scheduled_date')
            ->paginate(20);

        return ApiResponse::success($items->through(fn (VaccinationSchedule $s) => [
            'id' => $s->id,
            'cattle_id' => $s->cattle_id,
            'cattle_code' => $s->cattle?->code,
            'vaccine' => $s->vaccine?->name,
            'scheduled_date' => optional($s->scheduled_date)?->toDateString(),
            'status' => $s->status,
            'status_label' => $s->statusLabel(),
        ]));
    }

    public function report(Request $request, Cattle $cattle, CattleTimelineService $timeline)
    {
        $this->authorize('view', $cattle);
        $period = DatePeriod::fromRequest($request, 'all', true);
        $report = $timeline->report($cattle, $period, excludeFailed: true);
        $report['timeline'] = $report['timeline']->map(fn ($i) => [
            'at' => $i['at'],
            'kind' => $i['kind'],
            'title' => $i['title'],
        ]);
        $report['period'] = [
            'range' => $period->range,
            'label' => $period->label(),
            'from' => $period->from?->toDateString(),
            'to' => $period->to?->toDateString(),
        ];
        $report['cattle'] = new CattleResource($cattle->load(['breed', 'farmer.user', 'latestWeight', 'latestLumpy', 'latestBcs']));

        return ApiResponse::success($report);
    }

    public function reportPdf(Request $request, Cattle $cattle, CattleReportPdfService $pdf)
    {
        $this->authorize('view', $cattle);

        return $pdf->download($cattle, DatePeriod::fromRequest($request, 'all', true));
    }

    protected function ensureFarmer(Request $request): void
    {
        abort_unless($request->user()->isPeternak(), 403, 'Aplikasi mobile hanya untuk peternak.');
    }
}
