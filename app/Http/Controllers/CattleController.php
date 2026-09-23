<?php

namespace App\Http\Controllers;

use App\Exceptions\AiServiceException;
use App\Http\Requests\Web\AiScanRequest;
use App\Http\Requests\Web\StoreCattleRequest;
use App\Http\Requests\Web\UpdateCattleRequest;
use App\Models\AiExamination;
use App\Models\BcsRecord;
use App\Models\Breed;
use App\Models\Cattle;
use App\Models\FarmerProfile;
use App\Models\FeedRecord;
use App\Models\HealthRecord;
use App\Models\ReproductionRecord;
use App\Models\VaccinationRecord;
use App\Models\VaccinationSchedule;
use App\Models\Vaccine;
use App\Models\WeightRecord;
use App\Services\AiExaminationService;
use App\Services\BcsService;
use App\Services\CattleService;
use App\Services\CattleTimelineService;
use App\Services\CattleReportPdfService;
use App\Services\MortalityService;
use App\Services\VaccinationService;
use App\Support\DatePeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CattleController extends PanelController
{
    public function index(Request $request, CattleService $service): View
    {
        $q = $service->queryFor($request->user());
        if ($search = $request->string('q')->toString()) {
            $q->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('farmer.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        return view('cattle.index', [
            'cattle' => $q->paginate(12)->withQueryString(),
            'panel' => $this->panel(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Cattle::class);

        return view('cattle.form', [
            'cattle' => new Cattle(['sex' => 'female', 'status' => 'active']),
            'breeds' => Breed::where('is_active', true)->orderBy('name')->get(),
            'farmers' => FarmerProfile::with('user')->orderBy('farm_name')->get(),
            'panel' => $this->panel(),
        ]);
    }

    public function store(StoreCattleRequest $request, CattleService $service): RedirectResponse
    {
        $cattle = $service->create($request->validated(), $request->user(), $request->file('main_photo'));

        return redirect()->route($this->routeName('cattle.show'), $cattle)
            ->with('status', 'Data sapi berhasil disimpan.');
    }

    public function show(Request $request, Cattle $cattle, CattleTimelineService $timeline): View
    {
        $this->authorize('view', $cattle);
        $tab = $request->string('tab', 'ringkasan')->toString();
        $cattle->load(['farmer.user', 'breed', 'latestWeight', 'latestLumpy', 'mortality', 'latestBcs.creator']);
        $period = $tab === 'laporan'
            ? DatePeriod::fromRequest($request, 'all', true)
            : null;

        return view('cattle.show', [
            'cattle' => $cattle,
            'tab' => $tab,
            'period' => $period,
            'report' => $timeline->report($cattle, $period, excludeFailed: $tab === 'laporan'),
            'vaccines' => Vaccine::where('is_active', true)->orderBy('name')->get(),
            'editHealth' => $this->ownedRecord(HealthRecord::class, $cattle, $request->integer('health')),
            'editFeed' => $this->ownedRecord(FeedRecord::class, $cattle, $request->integer('feed')),
            'editRepro' => $this->ownedRecord(ReproductionRecord::class, $cattle, $request->integer('repro')),
            'editSchedule' => $this->ownedRecord(VaccinationSchedule::class, $cattle, $request->integer('schedule')),
            'editVaxRecord' => $this->ownedRecord(VaccinationRecord::class, $cattle, $request->integer('vax')),
            'panel' => $this->panel(),
        ]);
    }

    public function reportPdf(Request $request, Cattle $cattle, CattleReportPdfService $pdf): Response|StreamedResponse|BinaryFileResponse
    {
        $this->authorize('view', $cattle);
        $period = DatePeriod::fromRequest($request, 'all', true);

        return $pdf->download($cattle, $period);
    }

    public function edit(Cattle $cattle): View
    {
        $this->authorize('update', $cattle);

        return view('cattle.form', [
            'cattle' => $cattle,
            'breeds' => Breed::where('is_active', true)->orderBy('name')->get(),
            'farmers' => FarmerProfile::with('user')->orderBy('farm_name')->get(),
            'panel' => $this->panel(),
        ]);
    }

    public function update(UpdateCattleRequest $request, Cattle $cattle, CattleService $service): RedirectResponse
    {
        $service->update($cattle, $request->validated(), $request->file('main_photo'));

        return redirect()->route($this->routeName('cattle.show'), $cattle)
            ->with('status', 'Data sapi diperbarui.');
    }

    public function destroy(Cattle $cattle): RedirectResponse
    {
        $this->authorize('delete', $cattle);
        $cattle->delete();

        return redirect()->route($this->routeName('cattle.index'))
            ->with('status', 'Data sapi dihapus.');
    }

    public function scanWeight(AiScanRequest $request, Cattle $cattle, AiExaminationService $ai): RedirectResponse
    {
        try {
            $exam = $ai->examineWeight($cattle, $request->user(), $request->file('image'));
        } catch (AiServiceException $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }

        return redirect()->route($this->routeName('cattle.show'), [$cattle, 'tab' => 'ai'])
            ->with('status', 'Estimasi Bobot AI selesai: '.$exam->estimated_weight_kg.' kg.');
    }

    public function scanLumpy(AiScanRequest $request, Cattle $cattle, AiExaminationService $ai): RedirectResponse
    {
        try {
            $exam = $ai->examineLumpy($cattle, $request->user(), $request->file('image'));
        } catch (AiServiceException $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }

        return redirect()->route($this->routeName('cattle.show'), [$cattle, 'tab' => 'ai'])
            ->with('status', $exam->lumpy_label);
    }

    public function scanCombined(AiScanRequest $request, Cattle $cattle, AiExaminationService $ai): RedirectResponse
    {
        try {
            $exam = $ai->examineCombined($cattle, $request->user(), $request->file('image'));
        } catch (AiServiceException $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }

        $msg = 'Analisis gabungan: '.$exam->status;
        if ($exam->estimated_weight_kg) {
            $msg .= ' · Bobot '.$exam->estimated_weight_kg.' kg';
        }
        if ($exam->lumpy_label) {
            $msg .= ' · '.$exam->lumpy_label;
        }

        return redirect()->route($this->routeName('cattle.show'), [$cattle, 'tab' => 'ai'])
            ->with('status', $msg);
    }

    public function saveLumpyHealth(Request $request, Cattle $cattle, AiExaminationService $ai): RedirectResponse
    {
        $this->authorize('update', $cattle);
        $exam = $cattle->aiExaminations()->where('id', $request->integer('examination_id'))->firstOrFail();
        $this->authorize('view', $cattle);
        $ai->saveLumpyToHealth($exam, $request->user());

        return back()->with('status', 'Hasil AI disimpan ke riwayat kesehatan.');
    }

    public function storeWeight(Request $request, Cattle $cattle): RedirectResponse
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:1', 'max:2000'],
            'measured_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        WeightRecord::create([
            'cattle_id' => $cattle->id,
            'source' => 'manual',
            'weight_kg' => $data['weight_kg'],
            'measured_at' => $data['measured_at'] ?? now(),
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Bobot manual disimpan.');
    }

    public function storeBcs(Request $request, Cattle $cattle, BcsService $bcs): RedirectResponse
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'score' => BcsService::scoreRules(),
            'notes' => ['nullable', 'string'],
            'assessed_at' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $path = $request->file('image')?->store('cattle', 'public');
        $row = $bcs->create(array_merge($data, ['cattle_id' => $cattle->id]), $request->user()->id, $path);

        return redirect()
            ->route($this->routeName('cattle.show'), [$cattle, 'tab' => 'bcs', 'record' => $row->id])
            ->with('status', 'Penilaian kondisi tubuh berhasil disimpan.');
    }

    public function storeHealth(Request $request, Cattle $cattle): RedirectResponse
    {
        $this->authorize('update', $cattle);
        $data = $this->healthRules($request);
        HealthRecord::create([
            ...$data,
            'cattle_id' => $cattle->id,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Catatan kesehatan disimpan.');
    }

    public function updateHealth(Request $request, HealthRecord $record): RedirectResponse
    {
        $this->authorize('update', $record->cattle);
        $record->update($this->healthRules($request));

        return back()->with('status', 'Catatan kesehatan diperbarui.');
    }

    public function destroyHealth(HealthRecord $record): RedirectResponse
    {
        $this->authorize('update', $record->cattle);
        $record->delete();

        return back()->with('status', 'Catatan kesehatan dihapus.');
    }

    public function storeHealthFromAi(AiScanRequest $request, Cattle $cattle, AiExaminationService $ai): RedirectResponse
    {
        $this->authorize('update', $cattle);
        try {
            $exam = $ai->examineLumpy($cattle, $request->user(), $request->file('image'));
        } catch (AiServiceException $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }
        $ai->saveLumpyToHealth($exam, $request->user());

        return redirect()->route($this->routeName('cattle.show'), [$cattle, 'tab' => 'kesehatan'])
            ->with('status', 'Hasil AI disimpan ke riwayat kesehatan.');
    }

    public function updateHealthWithAi(Request $request, HealthRecord $record, AiExaminationService $ai): RedirectResponse
    {
        $this->authorize('update', $record->cattle);
        $this->authorize('examine', $record->cattle);
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        try {
            $exam = $ai->examineLumpy($record->cattle, $request->user(), $request->file('image'));
        } catch (AiServiceException $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }
        $ai->saveLumpyToHealth($exam, $request->user(), $record);

        return redirect()->route($this->routeName('cattle.show'), [$record->cattle, 'tab' => 'kesehatan', 'health' => $record->id])
            ->with('status', 'Catatan kesehatan diperbarui dari hasil AI.');
    }

    public function destroyExamination(AiExamination $exam): RedirectResponse
    {
        $this->authorize('update', $exam->cattle);
        $exam->delete();

        return back()->with('status', 'Indikasi pemeriksaan AI dihapus.');
    }

    public function storeSchedule(Request $request, Cattle $cattle, VaccinationService $vaccination): RedirectResponse
    {
        $this->authorize('update', $cattle);
        $data = $request->validate([
            'vaccine_id' => ['required', 'exists:vaccines,id'],
            'scheduled_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $vaccination->schedule($cattle, $request->user(), $data);

        return back()->with('status', 'Jadwal vaksin ditambahkan.');
    }

    public function storeFeed(Request $request, Cattle $cattle): RedirectResponse
    {
        $this->authorize('update', $cattle);
        $data = $this->feedRules($request);
        FeedRecord::create([
            ...$data,
            'cattle_id' => $cattle->id,
            'fed_at' => $data['fed_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Catatan pakan disimpan.');
    }

    public function storeReproduction(Request $request, Cattle $cattle): RedirectResponse
    {
        $this->authorize('update', $cattle);
        ReproductionRecord::create([
            ...$this->reproductionRules($request),
            'cattle_id' => $cattle->id,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Catatan reproduksi disimpan.');
    }

    public function storeMortality(Request $request, Cattle $cattle, MortalityService $mortality): RedirectResponse
    {
        $this->authorize('recordMortality', $cattle);
        $data = $request->validate([
            'died_at' => ['required', 'date'],
            'suspected_cause' => ['nullable', 'string', 'max:160'],
            'confirmed_cause' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:8192'],
        ]);
        $mortality->record($cattle, $request->user(), $data, $request->file('attachment'));

        return redirect()->route($this->routeName('cattle.show'), [$cattle, 'tab' => 'kematian'])
            ->with('status', 'Data kematian dicatat. Status sapi menjadi Meninggal.');
    }

    public function updateSchedule(Request $request, VaccinationSchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule->cattle);
        $data = $request->validate([
            'vaccine_id' => ['required', 'exists:vaccines,id'],
            'scheduled_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $schedule->update($data);

        return back()->with('status', 'Jadwal vaksin diperbarui.');
    }

    public function destroySchedule(VaccinationSchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule->cattle);
        $schedule->delete();

        return back()->with('status', 'Jadwal vaksin dihapus.');
    }

    public function updateVaccinationRecord(Request $request, VaccinationRecord $record): RedirectResponse
    {
        $this->authorize('update', $record->cattle);
        $data = $request->validate([
            'vaccine_id' => ['required', 'exists:vaccines,id'],
            'administered_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $record->update($data);

        return back()->with('status', 'Riwayat vaksin diperbarui.');
    }

    public function destroyVaccinationRecord(VaccinationRecord $record): RedirectResponse
    {
        $this->authorize('update', $record->cattle);
        $record->delete();

        return back()->with('status', 'Riwayat vaksin dihapus.');
    }

    public function updateFeed(Request $request, FeedRecord $feed): RedirectResponse
    {
        $this->authorize('update', $feed->cattle);
        $feed->update($this->feedRules($request));

        return back()->with('status', 'Catatan pakan diperbarui.');
    }

    public function destroyFeed(FeedRecord $feed): RedirectResponse
    {
        $this->authorize('update', $feed->cattle);
        $feed->delete();

        return back()->with('status', 'Catatan pakan dihapus.');
    }

    public function updateReproduction(Request $request, ReproductionRecord $record): RedirectResponse
    {
        $this->authorize('update', $record->cattle);
        $record->update($this->reproductionRules($request));

        return back()->with('status', 'Catatan reproduksi diperbarui.');
    }

    public function destroyReproduction(ReproductionRecord $record): RedirectResponse
    {
        $this->authorize('update', $record->cattle);
        $record->delete();

        return back()->with('status', 'Catatan reproduksi dihapus.');
    }

    protected function healthRules(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'symptoms' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'medicine' => ['nullable', 'string', 'max:160'],
            'veterinarian' => ['nullable', 'string', 'max:160'],
            'occurred_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }

    protected function feedRules(Request $request): array
    {
        return $request->validate([
            'feed_name' => ['required', 'string', 'max:160'],
            'quantity' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:30'],
            'cost' => ['nullable', 'numeric'],
            'fed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function reproductionRules(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:heat,mating,insemination,pregnancy_check,pregnant,birth,other'],
            'event_date' => ['required', 'date'],
            'partner_code' => ['nullable', 'string', 'max:50'],
            'inseminator' => ['nullable', 'string', 'max:120'],
            'pregnancy_status' => ['nullable', 'string', 'max:80'],
            'expected_birth_date' => ['nullable', 'date'],
            'actual_birth_date' => ['nullable', 'date'],
            'calf_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function ownedRecord(string $class, Cattle $cattle, int $id): mixed
    {
        if ($id < 1) {
            return null;
        }

        return $class::query()->where('cattle_id', $cattle->id)->find($id);
    }
}
