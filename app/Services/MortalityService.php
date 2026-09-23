<?php

namespace App\Services;

use App\Models\Cattle;
use App\Models\MortalityRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MortalityService
{
    public function record(Cattle $cattle, User $user, array $data, ?UploadedFile $attachment = null): MortalityRecord
    {
        if ($cattle->mortality) {
            throw new RuntimeException('Data kematian untuk sapi ini sudah tercatat.');
        }

        return DB::transaction(function () use ($cattle, $user, $data, $attachment) {
            $path = $attachment?->store('mortality', 'public');

            $record = MortalityRecord::create([
                'cattle_id' => $cattle->id,
                'died_at' => $data['died_at'] ?? now(),
                'suspected_cause' => $data['suspected_cause'] ?? null,
                'confirmed_cause' => $data['confirmed_cause'] ?? null,
                'notes' => $data['notes'] ?? null,
                'attachment' => $path,
                'reported_by' => $user->id,
            ]);

            $cattle->update(['status' => 'dead']);

            return $record;
        });
    }
}
