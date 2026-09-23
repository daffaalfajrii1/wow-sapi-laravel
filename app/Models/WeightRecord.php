<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightRecord extends Model
{
    protected $fillable = [
        'cattle_id',
        'ai_examination_id',
        'source',
        'weight_kg',
        'measured_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'float',
            'measured_at' => 'datetime',
        ];
    }

    public function cattle(): BelongsTo
    {
        return $this->belongsTo(Cattle::class);
    }

    public function examination(): BelongsTo
    {
        return $this->belongsTo(AiExamination::class, 'ai_examination_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceLabel(): string
    {
        return $this->source === 'ai' ? 'Estimasi AI' : 'Timbangan Manual';
    }
}
