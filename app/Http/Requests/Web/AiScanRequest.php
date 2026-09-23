<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class AiScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cattle = $this->route('cattle');

        return $cattle && $this->user()?->can('examine', $cattle);
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Unggah foto sapi terlebih dahulu.',
            'image.image' => 'Berkas harus berupa gambar JPG atau PNG.',
        ];
    }
}
