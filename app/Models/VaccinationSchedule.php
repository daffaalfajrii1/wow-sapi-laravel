<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VaccinationSchedule extends Model
{
    protected $fillable = [
        'cattle_id',
        'vaccine_id',
        'scheduled_date',
        'notes',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function record(): HasOne
    {
        return $this->hasOne(VaccinationRecord::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'done' => 'Selesai',
            'missed' => 'Terlewat',
            'cancelled' => 'Dibatalkan',
            default => 'Terjadwal',
        };
    }
}
