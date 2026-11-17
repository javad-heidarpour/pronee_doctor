<?php

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreGalleryImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && $user->is_active;
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
