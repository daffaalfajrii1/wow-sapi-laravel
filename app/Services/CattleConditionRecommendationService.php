<?php

namespace App\Services;

use App\Models\BcsRecord;
use App\Models\Cattle;
use App\Models\WeightRecord;

class CattleConditionRecommendationService
{
    public function generate(Cattle $cattle, BcsRecord $bcs, ?WeightRecord $latestWeight = null): array
    {
        $score = (float) $bcs->score;
        [$summary, $items] = $this->baseForScore($score);

        $latestWeight ??= $cattle->latestWeight;
        $previousWeight = $cattle->weightRecords()
            ->when($latestWeight, fn ($q) => $q->where('id', '!=', $latestWeight->id))
            ->orderByDesc('measured_at')
            ->first();

        $previousBcs = $cattle->bcsRecords()
            ->where('id', '!=', $bcs->id)
            ->latest('assessed_at')
            ->first();

        if ($latestWeight && $previousWeight) {
            $delta = round((float) $latestWeight->weight_kg - (float) $previousWeight->weight_kg, 1);
            $sign = $delta > 0 ? '+' : '';
            if (abs($delta) >= 0.1) {
                $items[] = 'Perubahan bobot: '.$sign.id_kg($delta).' ('.id_kg($previousWeight->weight_kg).' → '.id_kg($latestWeight->weight_kg).').';
            }

            if ($previousBcs && $delta > 0 && $score > (float) $previousBcs->score) {
                $items[] = 'Bobot naik '.id_kg($delta).'. BCS meningkat dari '.$this->fmt($previousBcs->score).' menjadi '.$this->fmt($score).'. Kondisi tubuh menunjukkan perkembangan yang lebih baik.';
            } elseif ($previousBcs && $delta < 0 && $score < (float) $previousBcs->score) {
                $items[] = 'Bobot turun '.id_kg(abs($delta)).'. BCS menurun dari '.$this->fmt($previousBcs->score).' menjadi '.$this->fmt($score).'. Pantau kondisi tubuh lebih dekat.';
            }
        }

        $purpose = strtolower((string) ($cattle->getAttribute('purpose') ?? $cattle->notes ?? ''));
        if ($score >= 2.0 && $score < 2.5 && str_contains($purpose, 'gemuk')) {
            $items[] = 'Pantau peningkatan bobot secara bertahap sesuai tujuan penggemukan.';
        }

        $pregnant = $cattle->reproductionRecords()
            ->whereIn('type', ['pregnant', 'pregnancy_check'])
            ->latest('event_date')
            ->first();

        if ($cattle->sex === 'female' && $pregnant && $score < 2.5) {
            $items[] = 'Perhatikan kondisi tubuh selama masa kebuntingan dan lakukan konsultasi dengan petugas jika kondisi terus menurun.';
        }

        return [
            'summary' => $summary,
            'items' => array_values(array_unique($items)),
        ];
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    protected function baseForScore(float $score): array
    {
        if ($score < 2.0) {
            return [
                'Kondisi tubuh sapi berada jauh di bawah kisaran ideal.',
                [
                    'Evaluasi kualitas dan kecukupan pakan.',
                    'Pastikan kebutuhan nutrisi ternak terpenuhi.',
                    'Pantau perubahan bobot secara lebih rutin.',
                    'Periksa kondisi kesehatan apabila berat badan tidak meningkat atau kondisi tubuh terus menurun.',
                    'Lakukan penilaian kondisi tubuh kembali dalam 14–30 hari.',
                ],
            ];
        }

        if ($score < 2.5) {
            return [
                'Kondisi tubuh sapi masih berada di bawah kisaran ideal.',
                [
                    'Evaluasi kualitas dan jumlah pakan.',
                    'Perhatikan kecukupan energi dan protein.',
                    'Pantau perkembangan bobot sapi.',
                    'Perhatikan kemungkinan gangguan kesehatan bila bobot sulit meningkat.',
                    'Lakukan evaluasi BCS kembali dalam 14–30 hari.',
                ],
            ];
        }

        if ($score <= 3.5) {
            return [
                'Kondisi tubuh sapi berada pada kisaran ideal.',
                [
                    'Pertahankan pola pemberian pakan.',
                    'Pantau bobot secara berkala.',
                    'Pertahankan kebersihan dan kesehatan ternak.',
                    'Lakukan penilaian kondisi tubuh secara rutin.',
                ],
            ];
        }

        if ($score <= 4.0) {
            return [
                'Kondisi tubuh sapi berada di atas kisaran ideal.',
                [
                    'Evaluasi pola pemberian pakan.',
                    'Pantau peningkatan bobot secara berkala.',
                    'Sesuaikan manajemen pakan dengan tujuan pemeliharaan.',
                    'Lakukan penilaian BCS kembali secara rutin.',
                ],
            ];
        }

        return [
            'Kondisi tubuh sapi berada cukup jauh di atas kisaran ideal.',
            [
                'Evaluasi kembali manajemen pemberian pakan.',
                'Pantau perkembangan bobot.',
                'Hindari peningkatan kondisi tubuh yang berlebihan.',
                'Sesuaikan pemeliharaan dengan tujuan ternak.',
                'Lakukan evaluasi kondisi tubuh secara berkala.',
            ],
        ];
    }

    protected function fmt(float $score): string
    {
        return number_format($score, 1, '.', '');
    }
}
