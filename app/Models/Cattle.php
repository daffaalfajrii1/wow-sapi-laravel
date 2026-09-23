<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cattle extends Model
{
    use SoftDeletes;

    protected $table = 'cattle';

    protected $fillable = [
        'farmer_id',
        'breed_id',
        'code',
        'name',
        'sex',
        'birth_date',
        'estimated_birth_date',
        'color',
        'origin',
        'entry_date',
        'main_photo',
        'status',
        'qr_token',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'entry_date' => 'date',
            'estimated_birth_date' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Cattle $cattle) {
            if (blank($cattle->qr_token)) {
                $cattle->qr_token = (string) Str::uuid();
            }
        });
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function aiExaminations(): HasMany
    {
        return $this->hasMany(AiExamination::class);
    }

    public function weightRecords(): HasMany
    {
        return $this->hasMany(WeightRecord::class);
    }

    public function bcsRecords(): HasMany
    {
        return $this->hasMany(BcsRecord::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function vaccinationSchedules(): HasMany
    {
        return $this->hasMany(VaccinationSchedule::class);
    }

    public function vaccinationRecords(): HasMany
    {
        return $this->hasMany(VaccinationRecord::class);
    }

    public function reproductionRecords(): HasMany
    {
        return $this->hasMany(ReproductionRecord::class);
    }

    public function feedRecords(): HasMany
    {
        return $this->hasMany(FeedRecord::class);
    }

    public function mortality(): HasOne
    {
        return $this->hasOne(MortalityRecord::class);
    }

    public function latestWeight(): HasOne
    {
        return $this->hasOne(WeightRecord::class)->latestOfMany('measured_at');
    }

    public function latestLumpy(): HasOne
    {
        return $this->hasOne(AiExamination::class)
            ->ofMany(['examined_at' => 'max'], function (Builder $query) {
                $query->whereIn('type', ['lumpy', 'combined'])
                    ->whereNotNull('lumpy_detected');
            });
    }

    public function latestBcs(): HasOne
    {
        return $this->hasOne(BcsRecord::class)->latestOfMany('assessed_at');
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('farmer', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    public function isDead(): bool
    {
        return $this->status === 'dead';
    }

    public function healthBadge(): array
    {
        $exam = $this->latestLumpy;

        if (! $exam || $exam->lumpy_detected === null) {
            return ['label' => 'Sehat', 'tone' => 'success', 'key' => 'sehat'];
        }

        if ($exam->lumpy_detected === false) {
            return ['label' => 'Sehat', 'tone' => 'success', 'key' => 'sehat'];
        }

        $prob = (float) $exam->lumpy_probability;
        if ($prob > 0 && $prob < 0.7) {
            return ['label' => 'Suspek', 'tone' => 'warning', 'key' => 'suspek'];
        }

        return ['label' => 'Terindikasi', 'tone' => 'danger', 'key' => 'positif'];
    }

    public function sexLabel(): string
    {
        return $this->sex === 'female' ? 'Betina' : 'Jantan';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sold' => 'Terjual',
            'dead' => 'Meninggal',
            default => 'Aktif',
        };
    }

    public function photoUrl(): string
    {
        if ($this->main_photo) {
            return asset('storage/'.$this->main_photo);
        }

        return asset('images/cow-placeholder.svg');
    }

    public function ageLabel(): string
    {
        if (! $this->birth_date) {
            return '—';
        }

        $diff = $this->birth_date->diff(now());
        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y.' tahun';
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m.' bulan';
        }
        if ($parts === []) {
            return 'Kurang dari 1 bulan';
        }

        return implode(' ', $parts);
    }

    public function belongsToUser(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->farmer?->user_id === $user->id;
    }
}
