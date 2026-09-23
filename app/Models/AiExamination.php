<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiExamination extends Model
{
    protected $fillable = [
        'cattle_id',
        'user_id',
        'type',
        'image_path',
        'cow_count',
        'detector_confidence',
        'estimated_weight_kg',
        'lumpy_detected',
        'lumpy_label',
        'lumpy_probability',
        'bcs_score',
        'bcs_category',
        'raw_response',
        'status',
        'examined_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'examined_at' => 'datetime',
            'lumpy_detected' => 'boolean',
            'detector_confidence' => 'float',
            'estimated_weight_kg' => 'float',
            'lumpy_probability' => 'float',
            'bcs_score' => 'float',
        ];
    }

    public function cattle(): BelongsTo
    {
        return $this->belongsTo(Cattle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function weightRecord(): HasOne
    {
        return $this->hasOne(WeightRecord::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'weight' => 'Estimasi Bobot AI',
            'lumpy' => 'AI Pemeriksaan Kesehatan',
            'combined' => 'Analisis Gabungan',
            'bcs' => 'BCS',
            default => $this->type,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'success' => 'Berhasil',
            'partial' => 'Sebagian',
            'failed', 'error' => 'Gagal',
            default => $this->status,
        };
    }

    public function timelineTitle(): string
    {
        $extra = '';
        if ($this->estimated_weight_kg) {
            $extra = ' · '.$this->estimated_weight_kg.' kg';
        } elseif ($this->lumpy_label) {
            $extra = ' · '.$this->lumpy_label;
        }

        return $this->typeLabel().' ('.$this->statusLabel().')'.$extra;
    }
}
