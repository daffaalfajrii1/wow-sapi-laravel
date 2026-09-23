<?php

namespace App\Services;

use App\Models\Cattle;
use App\Models\User;
use App\Models\VaccinationRecord;
use App\Models\VaccinationSchedule;
use Illuminate\Support\Facades\DB;

class VaccinationService
{
    public function schedule(Cattle $cattle, User $user, array $data): VaccinationSchedule
    {
        return VaccinationSchedule::create([
            'cattle_id' => $cattle->id,
            'vaccine_id' => $data['vaccine_id'],
            'scheduled_date' => $data['scheduled_date'],
            'notes' => $data['notes'] ?? null,
            'status' => 'scheduled',
            'created_by' => $user->id,
        ]);
    }

    public function complete(VaccinationSchedule $schedule, User $user, array $data = []): VaccinationRecord
    {
        return DB::transaction(function () use ($schedule, $user, $data) {
            $schedule->update(['status' => 'done']);

            return VaccinationRecord::create([
                'cattle_id' => $schedule->cattle_id,
                'vaccine_id' => $schedule->vaccine_id,
                'vaccination_schedule_id' => $schedule->id,
                'administered_at' => $data['administered_at'] ?? now(),
                'batch_no' => $data['batch_no'] ?? null,
                'dose' => $data['dose'] ?? null,
                'officer' => $data['officer'] ?? $user->name,
                'notes' => $data['notes'] ?? $schedule->notes,
                'created_by' => $user->id,
            ]);
        });
    }
}
