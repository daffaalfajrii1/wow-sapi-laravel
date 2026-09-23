<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MortalityRecord extends Model
{
    protected $fillable = [
        'cattle_id',
        'died_at',
        'suspected_cause',
        'confirmed_cause',
        'notes',
        'attachment',
        'reported_by',
    ];

    protected function casts(): array
    {
        return [
            'died_at' => 'datetime',
        ];
    }

    public function cattle(): BelongsTo
    {
        return $this->belongsTo(Cattle::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
