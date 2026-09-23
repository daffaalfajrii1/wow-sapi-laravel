<?php

namespace App\Http\Resources;

use App\Models\Cattle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cattle */
class CattleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $badge = $this->healthBadge();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'sex' => $this->sex,
            'sex_label' => $this->sexLabel(),
            'birth_date' => optional($this->birth_date)?->toDateString(),
            'estimated_birth_date' => $this->estimated_birth_date,
            'color' => $this->color,
            'origin' => $this->origin,
            'entry_date' => optional($this->entry_date)?->toDateString(),
            'main_photo_url' => $this->photoUrl(),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'qr_token' => $this->qr_token,
            'notes' => $this->notes,
            'health' => $badge,
            'latest_weight_kg' => $this->latestWeight?->weight_kg,
            'latest_bcs' => $this->whenLoaded('latestBcs', fn () => $this->latestBcs ? [
                'score' => $this->latestBcs->score,
                'category' => $this->latestBcs->category,
                'weight_kg' => $this->latestBcs->weight_kg_snapshot,
                'assessed_at' => optional($this->latestBcs->assessed_at)?->toIso8601String(),
                'recommendation_summary' => $this->latestBcs->recommendation_summary,
                'recommendations' => $this->latestBcs->recommendations ?? [],
            ] : null),
            'breed' => $this->whenLoaded('breed', fn () => [
                'id' => $this->breed?->id,
                'name' => $this->breed?->name,
            ]),
            'farmer' => $this->whenLoaded('farmer', fn () => [
                'id' => $this->farmer?->id,
                'farm_name' => $this->farmer?->farm_name,
                'owner' => $this->farmer?->user?->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
