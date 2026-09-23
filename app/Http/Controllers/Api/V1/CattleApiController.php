<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AiServiceException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CattleResource;
use App\Models\AiExamination;
use App\Models\BcsRecord;
use App\Models\Breed;
use App\Models\Cattle;
use App\Models\DeviceToken;
use App\Models\FeedRecord;
use App\Models\HealthRecord;
use App\Models\ReproductionRecord;
use App\Models\Vaccine;
use App\Models\VaccinationRecord;
use App\Models\VaccinationSchedule;
use App\Models\WeightRecord;
use App\Services\AiExaminationService;
use App\Services\BcsService;
use App\Services\CattleService;
use App\Services\CattleTimelineService;
use App\Services\MortalityService;
use App\Services\PushNotificationService;
use App\Services\VaccinationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CattleApiController extends Controller
{
    public function index(Request $request, CattleService $service)
    {
        $this->authorize('viewAny', Cattle::class);
        $q = $service->queryFor($request->user());
        if ($search = $request->string('q')->toString()) {
            $q->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }
        $items = $q->paginate(20);

        return ApiResponse::success(CattleResource::collection($items)->response()->getData(true));
    }

    public function store(Request $request, CattleService $service)
    {
        $this->authorize('create', Cattle::class);
        $data = $request->validate([
            'breed_id' => ['required', 'exists:breeds,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'estimated_birth_date' => ['sometimes', 'boolean'],
            'color' => ['nullable', 'string', 'max:80'],
            'origin' => ['nullable', 'string', 'max:120'],
            'entry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'main_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'farmer_id' => ['sometimes', 'exists:farmer_profiles,id'],
        ]);
        $cattle = $service->create($data, $request->user(), $request->file('main_photo'));

        return ApiResponse::success(new CattleResource($cattle->load(['breed', 'farmer.user'])), 'Sapi disimpan.', 201);
    }

    public function show(Cattle $cattle)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success(new CattleResource($cattle->load(['breed', 'farmer.user', 'latestWeight', 'latestLumpy', 'latestBcs'])));
    }

    public function update(Request $request, Cattle $cattle, CattleService $service)
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'breed_id' => ['sometimes', 'exists:breeds,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'sex' => ['sometimes', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'estimated_birth_date' => ['sometimes', 'boolean'],
            'color' => ['nullable', 'string'],
            'origin' => ['nullable', 'string'],
            'entry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'main_photo' => ['nullable', 'image', 'max:5120'],
            'farmer_id' => ['sometimes', 'exists:farmer_profiles,id'],
        ]);
        $service->update($cattle, $data, $request->file('main_photo'));

        return ApiResponse::success(new CattleResource($cattle->fresh()->load(['breed', 'farmer.user'])), 'Sapi diperbarui.');
    }

    public function destroy(Cattle $cattle)
    {
        $this->authorize('delete', $cattle);
        $cattle->delete();

        return ApiResponse::success(null, 'Data sapi dihapus.');
    }

    public function timeline(Cattle $cattle, CattleTimelineService $timeline)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success($timeline->build($cattle));
    }

    public function report(Cattle $cattle, CattleTimelineService $timeline)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success($timeline->report($cattle));
    }

    public function aiWeight(Request $request, Cattle $cattle, AiExaminationService $ai)
    {
        return $this->runAi($request, $cattle, fn () => $ai->examineWeight($cattle, $request->user(), $request->file('image')));
    }

    public function aiLumpy(Request $request, Cattle $cattle, AiExaminationService $ai)
    {
        return $this->runAi($request, $cattle, fn () => $ai->examineLumpy($cattle, $request->user(), $request->file('image')));
    }

    public function aiCombined(Request $request, Cattle $cattle, AiExaminationService $ai)
    {
        return $this->runAi($request, $cattle, fn () => $ai->examineCombined($cattle, $request->user(), $request->file('image')));
    }

    public function aiHistory(Cattle $cattle)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success($cattle->aiExaminations()->latest('examined_at')->get()->map(fn ($e) => [
            'id' => $e->id,
            'type' => $e->type,
            'type_label' => $e->typeLabel(),
            'status' => $e->status,
            'estimated_weight_kg' => $e->estimated_weight_kg,
            'lumpy_label' => $e->lumpy_label,
            'lumpy_detected' => $e->lumpy_detected,
            'detector_confidence' => $e->detector_confidence,
            'image_url' => $e->imageUrl(),
            'examined_at' => $e->examined_at,
        ]));
    }

    public function weights(Cattle $cattle)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success($cattle->weightRecords()->orderBy('measured_at')->get());
    }

    public function storeWeight(Request $request, Cattle $cattle)
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:1'],
            'measured_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $row = WeightRecord::create([
            'cattle_id' => $cattle->id,
            'source' => 'manual',
            'weight_kg' => $data['weight_kg'],
            'measured_at' => $data['measured_at'] ?? now(),
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return ApiResponse::success($row, 'Bobot disimpan.', 201);
    }

    public function bcs(Cattle $cattle)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success($cattle->bcsRecords()->latest('assessed_at')->get()->map(fn ($r) => [
            'id' => $r->id,
            'score' => $r->score,
            'category' => $r->category,
            'weight_kg_snapshot' => $r->weight_kg_snapshot,
            'notes' => $r->notes,
            'assessed_at' => optional($r->assessed_at)?->toIso8601String(),
            'recommendation_summary' => $r->recommendation_summary,
            'recommendations' => $r->recommendations ?? [],
            'image_url' => $r->imageUrl(),
        ]));
    }

    public function storeBcs(Request $request, Cattle $cattle, BcsService $bcs)
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'score' => BcsService::scoreRules(),
            'notes' => ['nullable', 'string'],
            'assessed_at' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
        $path = $request->file('image')?->store('cattle', 'public');
        $row = $bcs->create(array_merge($data, ['cattle_id' => $cattle->id]), $request->user()->id, $path);

        return ApiResponse::success([
            'score' => $row->score,
            'category' => $row->category,
            'weight_kg' => $row->weight_kg_snapshot,
            'recommendation' => $row->recommendation(),
        ], 'Penilaian kondisi tubuh berhasil disimpan.', 201);
    }

    public function health(Cattle $cattle)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success($cattle->healthRecords()->latest('occurred_at')->get());
    }

    public function storeHealth(Request $request, Cattle $cattle)
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'type' => ['required', 'string'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'symptoms' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'medicine' => ['nullable', 'string'],
            'veterinarian' => ['nullable', 'string'],
            'occurred_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);
        $row = HealthRecord::create([
            ...$data,
            'cattle_id' => $cattle->id,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return ApiResponse::success($row, 'Catatan kesehatan disimpan.', 201);
    }

    public function updateHealth(Request $request, HealthRecord $record)
    {
        $this->authorize('update', $record->cattle);
        $data = $request->validate([
            'type' => ['required', 'string'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'symptoms' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'medicine' => ['nullable', 'string'],
            'veterinarian' => ['nullable', 'string'],
            'occurred_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);
        $record->update($data);

        return ApiResponse::success($record->fresh(), 'Catatan kesehatan diperbarui.');
    }

    public function destroyHealth(HealthRecord $record)
    {
        $this->authorize('update', $record->cattle);
        $record->delete();

        return ApiResponse::success(null, 'Catatan kesehatan dihapus.');
    }

    public function saveLumpyHealth(Request $request, Cattle $cattle, AiExaminationService $ai)
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'examination_id' => ['required', 'integer'],
        ]);
        $exam = $cattle->aiExaminations()->where('id', $data['examination_id'])->firstOrFail();
        abort_unless(filled($exam->lumpy_label), 422, 'Hasil pemeriksaan tidak punya indikasi kesehatan.');
        $row = $ai->saveLumpyToHealth($exam, $request->user());

        return ApiResponse::success($row, 'Hasil AI disimpan ke riwayat kesehatan.', 201);
    }

    public function storeHealthFromAi(Request $request, Cattle $cattle, AiExaminationService $ai)
    {
        $this->authorize('update', $cattle);

        return $this->runAi($request, $cattle, function () use ($request, $cattle, $ai) {
            $exam = $ai->examineLumpy($cattle, $request->user(), $request->file('image'));
            $ai->saveLumpyToHealth($exam, $request->user());

            return $exam;
        });
    }

    public function updateHealthWithAi(Request $request, HealthRecord $record, AiExaminationService $ai)
    {
        $this->authorize('update', $record->cattle);

        return $this->runAi($request, $record->cattle, function () use ($request, $record, $ai) {
            $exam = $ai->examineLumpy($record->cattle, $request->user(), $request->file('image'));
            $ai->saveLumpyToHealth($exam, $request->user(), $record);

            return $exam;
        });
    }

    public function destroyExamination(AiExamination $exam)
    {
        $this->authorize('update', $exam->cattle);
        $exam->delete();

        return ApiResponse::success(null, 'Indikasi pemeriksaan AI dihapus.');
    }

    public function vaccinations(Cattle $cattle)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success([
            'schedules' => $cattle->vaccinationSchedules()->with('vaccine')->get(),
            'records' => $cattle->vaccinationRecords()->with('vaccine')->get(),
        ]);
    }

    public function storeSchedule(Request $request, Cattle $cattle, VaccinationService $service)
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'vaccine_id' => ['required', 'exists:vaccines,id'],
            'scheduled_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        return ApiResponse::success($service->schedule($cattle, $request->user(), $data), 'Jadwal vaksin dibuat.', 201);
    }

    public function updateSchedule(Request $request, VaccinationSchedule $schedule)
    {
        $this->authorize('update', $schedule->cattle);
        $data = $request->validate([
            'vaccine_id' => ['required', 'exists:vaccines,id'],
            'scheduled_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $schedule->update($data);

        return ApiResponse::success($schedule->fresh('vaccine'), 'Jadwal vaksin diperbarui.');
    }

    public function destroySchedule(VaccinationSchedule $schedule)
    {
        $this->authorize('update', $schedule->cattle);
        $schedule->delete();

        return ApiResponse::success(null, 'Jadwal vaksin dihapus.');
    }

    public function updateVaccinationRecord(Request $request, VaccinationRecord $record)
    {
        $this->authorize('update', $record->cattle);
        $data = $request->validate([
            'vaccine_id' => ['required', 'exists:vaccines,id'],
            'administered_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $record->update($data);

        return ApiResponse::success($record->fresh('vaccine'), 'Riwayat vaksin diperbarui.');
    }

    public function destroyVaccinationRecord(VaccinationRecord $record)
    {
        $this->authorize('update', $record->cattle);
        $record->delete();

        return ApiResponse::success(null, 'Riwayat vaksin dihapus.');
    }

    public function completeSchedule(Request $request, VaccinationSchedule $schedule, VaccinationService $service)
    {
        $this->authorize('update', $schedule->cattle);
        $row = $service->complete($schedule, $request->user(), $request->validate([
            'batch_no' => ['nullable', 'string'],
            'dose' => ['nullable', 'string'],
            'officer' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]));

        return ApiResponse::success($row, 'Vaksinasi selesai.');
    }

    public function reproduction(Cattle $cattle)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success($cattle->reproductionRecords()->latest('event_date')->get());
    }

    public function storeReproduction(Request $request, Cattle $cattle)
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'type' => ['required', 'in:heat,mating,insemination,pregnancy_check,pregnant,birth,other'],
            'event_date' => ['required', 'date'],
            'partner_code' => ['nullable', 'string'],
            'inseminator' => ['nullable', 'string'],
            'pregnancy_status' => ['nullable', 'string'],
            'expected_birth_date' => ['nullable', 'date'],
            'actual_birth_date' => ['nullable', 'date'],
            'calf_count' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);
        $row = ReproductionRecord::create([...$data, 'cattle_id' => $cattle->id, 'created_by' => $request->user()->id]);

        return ApiResponse::success($row, 'Catatan reproduksi disimpan.', 201);
    }

    public function updateReproduction(Request $request, ReproductionRecord $record)
    {
        $this->authorize('update', $record->cattle);
        $data = $request->validate([
            'type' => ['required', 'in:heat,mating,insemination,pregnancy_check,pregnant,birth,other'],
            'event_date' => ['required', 'date'],
            'partner_code' => ['nullable', 'string'],
            'inseminator' => ['nullable', 'string'],
            'pregnancy_status' => ['nullable', 'string'],
            'expected_birth_date' => ['nullable', 'date'],
            'actual_birth_date' => ['nullable', 'date'],
            'calf_count' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);
        $record->update($data);

        return ApiResponse::success($record->fresh(), 'Catatan reproduksi diperbarui.');
    }

    public function destroyReproduction(ReproductionRecord $record)
    {
        $this->authorize('update', $record->cattle);
        $record->delete();

        return ApiResponse::success(null, 'Catatan reproduksi dihapus.');
    }

    public function feeds(Cattle $cattle)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success([
            'items' => $cattle->feedRecords()->latest('fed_at')->get(),
            'total_cost' => $cattle->feedRecords()->sum('cost'),
        ]);
    }

    public function storeFeed(Request $request, Cattle $cattle)
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'feed_name' => ['required', 'string'],
            'quantity' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric'],
            'fed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $row = FeedRecord::create([
            ...$data,
            'cattle_id' => $cattle->id,
            'fed_at' => $data['fed_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return ApiResponse::success($row, 'Catatan pakan disimpan.', 201);
    }

    public function updateFeed(Request $request, FeedRecord $feed)
    {
        $this->authorize('update', $feed->cattle);
        $data = $request->validate([
            'feed_name' => ['required', 'string'],
            'quantity' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric'],
            'fed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $feed->update($data);

        return ApiResponse::success($feed->fresh(), 'Catatan pakan diperbarui.');
    }

    public function destroyFeed(FeedRecord $feed)
    {
        $this->authorize('update', $feed->cattle);
        $feed->delete();

        return ApiResponse::success(null, 'Catatan pakan dihapus.');
    }

    public function mortality(Cattle $cattle)
    {
        $this->authorize('view', $cattle);

        return ApiResponse::success($cattle->mortality);
    }

    public function storeMortality(Request $request, Cattle $cattle, MortalityService $service)
    {
        $this->authorize('recordMortality', $cattle);
        $data = $request->validate([
            'died_at' => ['required', 'date'],
            'suspected_cause' => ['nullable', 'string'],
            'confirmed_cause' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:8192'],
        ]);
        $row = $service->record($cattle, $request->user(), $data, $request->file('attachment'));

        return ApiResponse::success($row, 'Kematian dicatat. Status sapi menjadi meninggal.', 201);
    }

    public function breeds()
    {
        return ApiResponse::success(Breed::where('is_active', true)->orderBy('name')->get());
    }

    public function vaccines()
    {
        return ApiResponse::success(Vaccine::where('is_active', true)->orderBy('name')->get());
    }

    public function storeDeviceToken(Request $request, PushNotificationService $push)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['required', 'string', 'max:30'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        return ApiResponse::success($push->register($request->user(), $data), 'Perangkat terdaftar.', 201);
    }

    public function destroyDeviceToken(Request $request, $id)
    {
        $token = DeviceToken::where('user_id', $request->user()->id)->findOrFail($id);
        $token->delete();

        return ApiResponse::success(null, 'Token perangkat dihapus.');
    }

    protected function runAi(Request $request, Cattle $cattle, callable $fn)
    {
        $this->authorize('examine', $cattle);
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240']]);
        try {
            $exam = $fn();
        } catch (AiServiceException $e) {
            return ApiResponse::error($e->getMessage(), $e->httpStatus, null, ['code' => $e->codeKey]);
        }

        return ApiResponse::success([
            'id' => $exam->id,
            'type' => $exam->type,
            'status' => $exam->status,
            'estimated_weight_kg' => $exam->estimated_weight_kg,
            'lumpy_label' => $exam->lumpy_label,
            'lumpy_detected' => $exam->lumpy_detected,
            'lumpy_probability' => $exam->lumpy_probability,
            'detector_confidence' => $exam->detector_confidence,
            'cow_count' => $exam->cow_count,
            'examined_at' => $exam->examined_at,
            'disclaimer' => $exam->type !== 'lumpy'
                ? 'Hasil merupakan estimasi AI dan bukan pengganti timbangan ternak.'
                : 'Hasil AI bukan diagnosis final. Jika terindikasi, lanjutkan pemeriksaan kesehatan hewan.',
        ], 'Pemeriksaan AI selesai.');
    }
}
