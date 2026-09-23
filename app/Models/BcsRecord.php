<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BcsRecord extends Model
{
    protected $fillable = [
        'cattle_id',
        'score',
        'category',
        'weight_kg_snapshot',
        'image_path',
        'notes',
        'recommendation_summary',
        'recommendations',
        'assessed_at',
        'created_by',
        'ai_examination_id',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'weight_kg_snapshot' => 'float',
            'recommendations' => 'array',
            'assessed_at' => 'datetime',
        ];
    }

    public function cattle(): BelongsTo
    {
        return $this->belongsTo(Cattle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function recommendation(): array
    {
        return [
            'summary' => $this->recommendation_summary,
            'items' => $this->recommendations ?? [],
        ];
    }
}
