<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCattleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Cattle::class) ?? false;
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
            'notes' => ['nullable', 'string'],
            'main_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];

        if ($this->user()?->isAdmin()) {
            $rules['farmer_id'] = ['required', 'exists:farmer_profiles,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'breed_id.required' => 'Pilih ras sapi.',
            'sex.required' => 'Pilih jenis kelamin.',
            'farmer_id.required' => 'Pilih peternak.',
        ];
    }
}
