<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaccinationRecord extends Model
{
    protected $fillable = [
        'cattle_id',
        'vaccine_id',
        'vaccination_schedule_id',
        'administered_at',
        'batch_no',
        'dose',
        'officer',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'administered_at' => 'datetime',
        ];
    }

    public function cattle(): BelongsTo
    {
        return $this->belongsTo(Cattle::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(VaccinationSchedule::class, 'vaccination_schedule_id');
    }
}
