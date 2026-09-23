<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'farm_name',
        'phone',
        'address',
        'village',
        'district',
        'regency',
        'province',
        'profile_photo',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cattle(): HasMany
    {
        return $this->hasMany(Cattle::class, 'farmer_id');
    }
}
