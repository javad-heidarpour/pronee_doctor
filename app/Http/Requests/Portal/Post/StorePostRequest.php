<?php

namespace App\Http\Requests\Portal\Post;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // یا منطق احراز هویت خاص
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:posts,slug',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'required|integer|exists:categories,id',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'author_id' => 'nullable|integer',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'keywords' => 'nullable|array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'is_pinned' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان مقاله الزامی است.',
            'slug.required' => 'نامک (Slug) الزامی است.',
            'slug.unique' => 'این نامک قبلاً استفاده شده است.',
            'content.required' => 'محتوای مقاله الزامی است.',
            'category_id.required' => 'انتخاب دسته‌بندی الزامی است.',
            'featured_image.image' => 'فایل باید تصویر باشد.',
            'featured_image.max' => 'حجم تصویر حداکثر ۵ مگابایت است.',
        ];
    }
}