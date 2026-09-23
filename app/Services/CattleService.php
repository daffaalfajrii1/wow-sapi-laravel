<?php

namespace App\Services;

use App\Models\Cattle;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CattleService
{
    public function create(array $data, User $actor, ?UploadedFile $photo = null): Cattle
    {
        return DB::transaction(function () use ($data, $actor, $photo) {
            $farmerId = $data['farmer_id'] ?? null;
            if (! $actor->isAdmin()) {
                $profile = $actor->farmerProfile;
                if (! $profile) {
                    throw new RuntimeException('Profil peternak belum lengkap.');
                }
                $farmerId = $profile->id;
            }

            $cattle = Cattle::create([
                'farmer_id' => $farmerId,
                'breed_id' => $data['breed_id'],
                'code' => $data['code'] ?? $this->nextCode(),
                'name' => $data['name'] ?? null,
                'sex' => $data['sex'],
                'birth_date' => $data['birth_date'] ?? null,
                'estimated_birth_date' => (bool) ($data['estimated_birth_date'] ?? false),
                'color' => $data['color'] ?? null,
                'origin' => $data['origin'] ?? null,
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'qr_token' => (string) Str::uuid(),
            ]);

            if ($photo) {
                $cattle->update(['main_photo' => $photo->store('cattle', 'public')]);
            }

            return $cattle;
        });
    }

    public function update(Cattle $cattle, array $data, ?UploadedFile $photo = null): Cattle
    {
        return DB::transaction(function () use ($cattle, $data, $photo) {
            $cattle->fill(collect($data)->only([
                'farmer_id',
                'breed_id',
                'name',
                'sex',
                'birth_date',
                'estimated_birth_date',
                'color',
                'origin',
                'entry_date',
                'status',
                'notes',
            ])->all());

            if ($photo) {
                $cattle->main_photo = $photo->store('cattle', 'public');
            }

            $cattle->save();

            return $cattle;
        });
    }

    public function nextCode(): string
    {
        return DB::transaction(function () {
            $max = Cattle::withTrashed()
                ->where('code', 'like', 'SAPI-%')
                ->lockForUpdate()
                ->get()
                ->map(fn (Cattle $c) => (int) preg_replace('/\D/', '', substr($c->code, 5)))
                ->max() ?? 0;

            do {
                $max++;
                $code = 'SAPI-'.str_pad((string) $max, 4, '0', STR_PAD_LEFT);
            } while (Cattle::withTrashed()->where('code', $code)->exists());

            return $code;
        });
    }

    public function queryFor(User $user)
    {
        return Cattle::query()
            ->with(['farmer.user', 'breed', 'latestWeight', 'latestLumpy'])
            ->ownedBy($user)
            ->latest();
    }

    public function ensureFarmerProfile(User $user, array $data = []): FarmerProfile
    {
        return $user->farmerProfile()->firstOrCreate(
            ['user_id' => $user->id],
            array_merge([
                'phone' => $data['phone'] ?? '-',
                'farm_name' => $data['farm_name'] ?? $user->name,
            ], $data),
        );
    }
}
