<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class StoreGalleryImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => 'required|array|max:10',
            'images.*' => 'image|max:5120',
            'caption' => 'nullable|string|max:500',
            'category' => 'required|in:Workplace,equipment,team,certificate,Heartfelt,Portfolio,Satisfaction,other',
        ];
    }
}
