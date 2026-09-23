<?php

namespace App\Services;

use App\Exceptions\AiServiceException;
use App\Models\AiExamination;
use App\Models\Cattle;
use App\Models\User;
use App\Models\WeightRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AiExaminationService
{
    public function __construct(private WowSapiAiService $ai)
    {
    }

    public function examineWeight(Cattle $cattle, User $user, UploadedFile $file): AiExamination
    {
        $stored = $this->ai->storeUpload($file, 'ai-examinations');

        try {
            $result = $this->ai->predictWeight($stored['absolute'], $stored['filename']);
        } catch (AiServiceException $e) {
            return $this->failed($cattle, $user, 'weight', $stored['path'], $e);
        }

        return DB::transaction(function () use ($cattle, $user, $stored, $result) {
            $exam = AiExamination::create([
                'cattle_id' => $cattle->id,
                'user_id' => $user->id,
                'type' => 'weight',
                'image_path' => $stored['path'],
                'cow_count' => $result['cow_count'] ?? 1,
                'detector_confidence' => $this->maxConfidence($result['detections'] ?? []),
                'estimated_weight_kg' => $result['estimated_weight_kg'] ?? null,
                'raw_response' => $result,
                'status' => 'success',
                'examined_at' => now(),
            ]);

            $this->storeWeight($cattle, $user, $exam, (float) $result['estimated_weight_kg']);

            return $exam;
        });
    }

    public function examineLumpy(Cattle $cattle, User $user, UploadedFile $file): AiExamination
    {
        $stored = $this->ai->storeUpload($file, 'ai-examinations');

        try {
            $result = $this->ai->predictLumpy($stored['absolute'], $stored['filename']);
        } catch (AiServiceException $e) {
            return $this->failed($cattle, $user, 'lumpy', $stored['path'], $e);
        }

        [$detected, $label] = $this->mapLumpy($result);

        return AiExamination::create([
            'cattle_id' => $cattle->id,
            'user_id' => $user->id,
            'type' => 'lumpy',
            'image_path' => $stored['path'],
            'cow_count' => $result['cow_count'] ?? null,
            'detector_confidence' => $this->maxConfidence($result['detections'] ?? []),
            'lumpy_detected' => $detected,
            'lumpy_label' => $label,
            'lumpy_probability' => $result['probability'] ?? null,
            'raw_response' => $result,
            'status' => 'success',
            'examined_at' => now(),
        ]);
    }

    public function examineCombined(Cattle $cattle, User $user, UploadedFile $file): AiExamination
    {
        $stored = $this->ai->storeUpload($file, 'ai-examinations');

        try {
            $result = $this->ai->analyzeCombined($stored['absolute'], $stored['filename']);
        } catch (AiServiceException $e) {
            return $this->failed($cattle, $user, 'combined', $stored['path'], $e);
        }

        return DB::transaction(function () use ($cattle, $user, $stored, $result) {
            $weight = $result['weight'] ?? null;
            $lumpy = $result['lumpy'] ?? null;
            $weightSkipped = $result['weight_skipped'] ?? null;
            $lumpySkipped = $result['lumpy_skipped'] ?? null;

            $status = 'success';
            if (($weightSkipped && $lumpy) || ($lumpySkipped && $weight) || ($weightSkipped && $lumpySkipped)) {
                $status = 'partial';
            }
            if (! $weight && ! $lumpy) {
                $status = 'failed';
            }

            [$detected, $label] = $lumpy ? $this->mapLumpy($lumpy) : [null, null];

            $exam = AiExamination::create([
                'cattle_id' => $cattle->id,
                'user_id' => $user->id,
                'type' => 'combined',
                'image_path' => $stored['path'],
                'cow_count' => $result['cow_count'] ?? null,
                'detector_confidence' => $this->maxConfidence($result['detections'] ?? []),
                'estimated_weight_kg' => $weight['estimated_weight_kg'] ?? null,
                'lumpy_detected' => $detected,
                'lumpy_label' => $label,
                'lumpy_probability' => $lumpy['probability'] ?? null,
                'raw_response' => $result,
                'status' => $status,
                'examined_at' => now(),
            ]);

            if ($weight && isset($weight['estimated_weight_kg'])) {
                $this->storeWeight($cattle, $user, $exam, (float) $weight['estimated_weight_kg']);
            }

            return $exam;
        });
    }

    public function saveLumpyToHealth(AiExamination $exam, User $user, ?\App\Models\HealthRecord $record = null): \App\Models\HealthRecord
    {
        $data = [
            'type' => 'lumpy',
            'title' => $exam->lumpy_label ?: 'Pemeriksaan Lumpy Skin AI',
            'description' => $exam->lumpy_detected
                ? 'Hasil AI menunjukkan indikasi Lumpy Skin. Disarankan melakukan pemeriksaan lanjutan oleh petugas kesehatan hewan.'
                : 'Hasil AI: tidak terindikasi Lumpy Skin.',
            'symptoms' => $exam->lumpy_detected ? 'Indikasi visual Lumpy Skin (hasil AI)' : null,
            'occurred_at' => $exam->examined_at ?? now(),
            'status' => $exam->lumpy_detected ? 'perlu_pemeriksaan' : 'sehat',
        ];

        if ($record) {
            abort_unless((int) $record->cattle_id === (int) $exam->cattle_id, 404);
            $record->update($data);

            return $record->fresh();
        }

        return \App\Models\HealthRecord::create([
            ...$data,
            'cattle_id' => $exam->cattle_id,
            'created_by' => $user->id,
        ]);
    }

    protected function failed(Cattle $cattle, User $user, string $type, string $path, AiServiceException $e): AiExamination
    {
        $exam = AiExamination::create([
            'cattle_id' => $cattle->id,
            'user_id' => $user->id,
            'type' => $type,
            'image_path' => $path,
            'cow_count' => $e->payload['cow_count'] ?? null,
            'raw_response' => [
                'ok' => false,
                'code' => $e->codeKey,
                'message' => $e->getMessage(),
                'payload' => $e->payload,
            ],
            'status' => 'failed',
            'examined_at' => now(),
        ]);

        $exam->setAttribute('error_message', $e->getMessage());
        $exam->setAttribute('error_code', $e->codeKey);

        throw $e;
    }

    protected function storeWeight(Cattle $cattle, User $user, AiExamination $exam, float $kg): void
    {
        WeightRecord::create([
            'cattle_id' => $cattle->id,
            'ai_examination_id' => $exam->id,
            'source' => 'ai',
            'weight_kg' => $kg,
            'measured_at' => $exam->examined_at,
            'notes' => 'Estimasi Bobot AI. Hasil merupakan estimasi AI dan bukan pengganti timbangan ternak.',
            'created_by' => $user->id,
        ]);
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public function mapLumpy(array $result): array
    {
        $positive = (bool) ($result['lumpy_positive'] ?? false);
        $rawLabel = (string) ($result['label'] ?? '');
        $probability = (float) ($result['probability'] ?? 0);

        if ($positive || str_contains(strtolower($rawLabel), 'lumpy')) {
            if ($probability > 0 && $probability < 0.7) {
                return [true, 'Perlu Pemeriksaan Lanjutan'];
            }

            return [true, 'Terindikasi Lumpy Skin'];
        }

        return [false, 'Tidak Terindikasi Lumpy Skin'];
    }

    protected function maxConfidence(array $detections): ?float
    {
        $conf = collect($detections)->pluck('confidence')->filter()->max();

        return $conf !== null ? (float) $conf : null;
    }
}
