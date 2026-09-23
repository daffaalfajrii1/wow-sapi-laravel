<?php

namespace App\Http\Controllers;

use App\Models\AiExamination;
use App\Models\Breed;
use App\Models\Cattle;
use App\Models\FarmerProfile;
use App\Models\FeedRecord;
use App\Models\HealthRecord;
use App\Models\MortalityRecord;
use App\Models\ReproductionRecord;
use App\Models\User;
use App\Models\VaccinationSchedule;
use App\Models\Vaccine;
use App\Services\CattleReportPdfService;
use App\Services\CattleService;
use App\Services\CattleTimelineService;
use App\Services\VaccinationService;
use App\Support\DatePeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ModuleController extends PanelController
{
    public function farmers(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $farmers = FarmerProfile::with('user')->withCount('cattle')->latest()->paginate(15);

        return view('admin.farmers.index', compact('farmers'));
    }

    public function farmerShow(FarmerProfile $farmer): View
    {
        $this->authorize('view', $farmer);
        $farmer->load(['user', 'cattle.breed']);

        return view('admin.farmers.show', compact('farmer'));
    }

    public function examinations(Request $request): View
    {
        $q = AiExamination::with(['cattle.farmer.user', 'user'])->latest('examined_at');
        if (! $request->user()->isAdmin()) {
            $ids = Cattle::ownedBy($request->user())->pluck('id');
            $q->whereIn('cattle_id', $ids);
        }

        return view('modules.examinations', [
            'items' => $q->paginate(20),
            'panel' => $this->panel(),
        ]);
    }

    public function health(Request $request): View
    {
        $q = HealthRecord::with(['cattle.farmer.user'])->latest('occurred_at');
        if (! $request->user()->isAdmin()) {
            $q->whereIn('cattle_id', Cattle::ownedBy($request->user())->pluck('id'));
        }

        return view('modules.health', ['items' => $q->paginate(20), 'panel' => $this->panel()]);
    }

    public function vaccinations(Request $request): View
    {
        $q = VaccinationSchedule::with(['cattle', 'vaccine'])->orderBy('scheduled_date');
        if (! $request->user()->isAdmin()) {
            $q->whereIn('cattle_id', Cattle::ownedBy($request->user())->pluck('id'));
        }

        return view('modules.vaccinations', ['items' => $q->paginate(20), 'panel' => $this->panel()]);
    }

    public function completeSchedule(Request $request, VaccinationSchedule $schedule, VaccinationService $service): RedirectResponse
    {
        $this->authorize('update', $schedule->cattle);
        $service->complete($schedule, $request->user(), $request->only(['batch_no', 'dose', 'officer', 'notes']));

        return back()->with('status', 'Vaksinasi ditandai selesai.');
    }

    public function feeds(Request $request): View
    {
        $q = FeedRecord::with(['cattle'])->latest('fed_at');
        if (! $request->user()->isAdmin()) {
            $q->whereIn('cattle_id', Cattle::ownedBy($request->user())->pluck('id'));
        }

        return view('modules.feeds', ['items' => $q->paginate(20), 'panel' => $this->panel()]);
    }

    public function reproduction(Request $request): View
    {
        $q = ReproductionRecord::with(['cattle'])->latest('event_date');
        if (! $request->user()->isAdmin()) {
            $q->whereIn('cattle_id', Cattle::ownedBy($request->user())->pluck('id'));
        }

        return view('modules.reproduction', ['items' => $q->paginate(20), 'panel' => $this->panel()]);
    }

    public function mortality(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $items = MortalityRecord::with(['cattle.farmer.user', 'reporter'])->latest('died_at')->paginate(20);

        return view('admin.mortality', compact('items'));
    }

    public function reports(Request $request, CattleTimelineService $timeline): View
    {
        $period = DatePeriod::fromRequest($request, 'all', true);
        $cattle = Cattle::ownedBy($request->user())->with(['farmer.user', 'breed'])->orderBy('code')->get();
        $selected = null;
        $report = null;
        if ($id = $request->integer('cattle_id')) {
            $selected = Cattle::ownedBy($request->user())
                ->with(['farmer.user', 'breed', 'latestWeight', 'latestLumpy', 'latestBcs'])
                ->findOrFail($id);
            $this->authorize('view', $selected);
            $report = $timeline->report($selected, $period, excludeFailed: true);
        }

        return view('modules.reports', [
            'cattleList' => $cattle,
            'selected' => $selected,
            'report' => $report,
            'period' => $period,
            'panel' => $this->panel(),
        ]);
    }

    public function reportPdf(Request $request, Cattle $cattle, CattleReportPdfService $pdf)
    {
        $this->authorize('view', $cattle);

        return $pdf->download($cattle, DatePeriod::fromRequest($request, 'all', true));
    }

    public function weights(Request $request): View
    {
        $items = Cattle::ownedBy($request->user())->with(['latestWeight', 'farmer.user'])->orderBy('code')->paginate(20);

        return view('modules.weights', ['items' => $items, 'panel' => $this->panel()]);
    }

    public function bcs(Request $request): View
    {
        $items = \App\Models\BcsRecord::with(['cattle', 'creator'])->whereIn(
            'cattle_id',
            Cattle::ownedBy($request->user())->pluck('id')
        )->latest('assessed_at')->paginate(20);

        return view('modules.bcs', ['items' => $items, 'panel' => $this->panel()]);
    }

    public function breeds(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.master.breeds', ['items' => Breed::orderBy('name')->paginate(20)]);
    }

    public function storeBreed(Request $request): RedirectResponse
    {
        $this->authorize('create', Breed::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        Breed::create($data);

        return back()->with('status', 'Ras sapi ditambahkan.');
    }

    public function updateBreed(Request $request, Breed $breed): RedirectResponse
    {
        $this->authorize('update', $breed);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $breed->update($data);

        return back()->with('status', 'Ras sapi diperbarui.');
    }

    public function vaccines(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.master.vaccines', ['items' => Vaccine::orderBy('name')->paginate(20)]);
    }

    public function storeVaccine(Request $request): RedirectResponse
    {
        $this->authorize('create', Vaccine::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'default_interval_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        Vaccine::create($data);

        return back()->with('status', 'Master vaksin ditambahkan.');
    }

    public function updateVaccine(Request $request, Vaccine $vaccine): RedirectResponse
    {
        $this->authorize('update', $vaccine);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'default_interval_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $vaccine->update($data);

        return back()->with('status', 'Master vaksin diperbarui.');
    }

    public function users(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $users = User::with('roles', 'farmerProfile')->latest()->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function storeUser(Request $request, CattleService $cattleService): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'role' => ['required', 'in:admin,peternak'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);
        Role::findOrCreate($data['role']);
        $user->assignRole($data['role']);
        if ($data['role'] === 'peternak') {
            $cattleService->ensureFarmerProfile($user, ['phone' => $data['phone'] ?? '-']);
        }

        return back()->with('status', 'Pengguna ditambahkan.');
    }

    public function settings(): View
    {
        return view('modules.settings', [
            'panel' => $this->panel(),
            'ai' => app(\App\Services\WowSapiAiService::class)->health(),
        ]);
    }

    public function notifications(): View
    {
        $items = auth()->user()->notifications()->paginate(20);

        return view('modules.notifications', ['items' => $items, 'panel' => $this->panel()]);
    }

    public function markNotifications(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Semua notifikasi ditandai dibaca.');
    }

    public function profile(): View
    {
        $user = auth()->user()->load('farmerProfile');

        return view('modules.profile', ['user' => $user, 'panel' => $this->panel()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'farm_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'village' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'regency' => ['nullable', 'string', 'max:80'],
            'province' => ['nullable', 'string', 'max:80'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
        $user = $request->user();
        $user->update(['name' => $data['name']]);
        if ($request->file('avatar')) {
            $user->update(['avatar' => $request->file('avatar')->store('profiles', 'public')]);
        }
        if ($user->isPeternak()) {
            $user->farmerProfile?->update(collect($data)->only([
                'farm_name', 'phone', 'address', 'village', 'district', 'regency', 'province',
            ])->all());
        }

        return back()->with('status', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.confirmed' => 'Ulangi kata sandi baru tidak sama.',
        ]);
        $request->user()->update(['password' => Hash::make($request->string('password'))]);

        return back()->with('status', 'Kata sandi diperbarui.');
    }

    public function search(Request $request): View
    {
        $q = $request->string('q')->toString();
        $cattle = Cattle::ownedBy($request->user())
            ->with(['farmer.user', 'breed'])
            ->when($q, fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            }))
            ->limit(20)
            ->get();

        $farmers = $request->user()->isAdmin()
            ? FarmerProfile::with('user')->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                ->orWhere('farm_name', 'like', "%{$q}%")
                ->limit(10)->get()
            : collect();

        return view('modules.search', compact('q', 'cattle', 'farmers') + ['panel' => $this->panel()]);
    }
}
