<?php

namespace App\Http\Requests\Web;

use App\Models\Cattle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCattleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cattle = $this->route('cattle');

        return $cattle instanceof Cattle && $this->user()?->can('update', $cattle);
    }

    public function rules(): array
    {
        $rules = [
            'breed_id' => ['required', 'exists:breeds,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date'],
            'estimated_birth_date' => ['sometimes', 'boolean'],
            'color' => ['nullable', 'string', 'max:80'],
            'origin' => ['nullable', 'string', 'max:120'],
            'entry_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['active', 'sold', 'dead'])],
            'notes' => ['nullable', 'string'],
            'main_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        if ($this->user()?->isAdmin()) {
            $rules['farmer_id'] = ['required', 'exists:farmer_profiles,id'];
        }

        return $rules;
    }
}
