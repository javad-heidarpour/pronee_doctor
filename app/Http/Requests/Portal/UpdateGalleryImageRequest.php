<?php

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGalleryImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && $user->is_active;
    }

    public function rules(): array
    {
        return [
            'caption' => 'nullable|string|max:500',
            'category' => 'required|in:Workplace,equipment,team,certificate,Heartfelt,Portfolio,Satisfaction,other',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
