<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReproductionRecord extends Model
{
    protected $fillable = [
        'cattle_id',
        'type',
        'event_date',
        'partner_code',
        'inseminator',
        'pregnancy_status',
        'expected_birth_date',
        'actual_birth_date',
        'calf_count',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'expected_birth_date' => 'date',
            'actual_birth_date' => 'date',
        ];
    }

    public function cattle(): BelongsTo
    {
        return $this->belongsTo(Cattle::class);
    }

    public static function typeLabels(): array
    {
        return [
            'heat' => 'Birahi',
            'mating' => 'Perkawinan',
            'insemination' => 'Inseminasi Buatan',
            'pregnancy_check' => 'Pemeriksaan Kebuntingan',
            'pregnant' => 'Bunting',
            'birth' => 'Kelahiran',
            'other' => 'Lainnya',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }
}
