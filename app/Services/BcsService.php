<?php

namespace App\Services;

use App\Models\BcsRecord;
use App\Models\Cattle;
use Illuminate\Validation\ValidationException;

class BcsService
{
    public static function category(float $score): string
    {
        return match (true) {
            $score < 2.0 => 'Sangat Kurus',
            $score < 2.5 => 'Kurus',
            $score <= 3.5 => 'Ideal',
            $score <= 4.0 => 'Gemuk',
            default => 'Sangat Gemuk',
        };
    }

    public static function isAllowedScore(mixed $value): bool
    {
        if (! is_numeric($value)) {
            return false;
        }

        $score = (float) $value;
        if ($score < 1 || $score > 5) {
            return false;
        }

        return abs(($score * 2) - round($score * 2)) < 0.001;
    }

    public static function scoreRules(): array
    {
        return [
            'required',
            'numeric',
            'min:1',
            'max:5',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! self::isAllowedScore($value)) {
                    $fail('Nilai BCS harus 1–5 dengan interval 0,5.');
                }
            },
        ];
    }

    public function create(array $data, int $userId, ?string $imagePath = null): BcsRecord
    {
        $score = (float) $data['score'];
        if (! self::isAllowedScore($score)) {
            throw ValidationException::withMessages([
                'score' => 'Nilai BCS harus 1–5 dengan interval 0,5.',
            ]);
        }

        $cattle = Cattle::with(['latestWeight', 'breed'])->findOrFail($data['cattle_id']);
        $latestWeight = $cattle->latestWeight;

        $record = BcsRecord::create([
            'cattle_id' => $cattle->id,
            'score' => $score,
            'category' => self::category($score),
            'weight_kg_snapshot' => $latestWeight?->weight_kg,
            'image_path' => $imagePath,
            'notes' => $data['notes'] ?? null,
            'assessed_at' => $data['assessed_at'] ?? now(),
            'created_by' => $userId,
        ]);

        $recommendation = app(CattleConditionRecommendationService::class)
            ->generate($cattle, $record, $latestWeight);

        $record->update([
            'recommendation_summary' => $recommendation['summary'],
            'recommendations' => $recommendation['items'],
        ]);

        return $record->fresh(['creator', 'cattle']);
    }
}
