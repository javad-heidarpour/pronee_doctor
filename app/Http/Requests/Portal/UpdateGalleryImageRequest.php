<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGalleryImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'caption' => 'nullable|string|max:255',
            'category' => 'required|in:clinic,equipment,team,certificate,other',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
