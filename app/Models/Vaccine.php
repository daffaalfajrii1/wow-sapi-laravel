<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vaccine extends Model
{
    protected $fillable = [
        'name',
        'description',
        'default_interval_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_interval_days' => 'integer',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(VaccinationSchedule::class);
    }
}
