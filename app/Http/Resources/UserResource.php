<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar ? asset('storage/'.$this->avatar) : null,
            'roles' => $this->getRoleNames(),
            'farmer_profile' => $this->whenLoaded('farmerProfile', fn () => $this->farmerProfile),
        ];
    }
}
