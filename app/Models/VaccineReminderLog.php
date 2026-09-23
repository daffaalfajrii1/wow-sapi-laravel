<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaccineReminderLog extends Model
{
    protected $fillable = [
        'vaccination_schedule_id',
        'reminder_type',
        'scheduled_date',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(VaccinationSchedule::class, 'vaccination_schedule_id');
    }
}
