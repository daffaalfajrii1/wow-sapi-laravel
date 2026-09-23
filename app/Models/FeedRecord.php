<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedRecord extends Model
{
    protected $fillable = [
        'cattle_id',
        'feed_name',
        'quantity',
        'unit',
        'cost',
        'fed_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'cost' => 'float',
            'fed_at' => 'datetime',
        ];
    }

    public function cattle(): BelongsTo
    {
        return $this->belongsTo(Cattle::class);
    }
}
