<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('web')->check();
    }

    public function rules(): array
    {
        return [

            'title' => [
                'required',
                'string',
                'max:255'
            ],

            'content' => [
                'required',
                'string'
            ],

            'category_id' => [
                'required',
                'exists:categories,id'
            ],

            'excerpt' => [
                'nullable',
                'string',
                'max:500'
            ],

            'meta_title' => [
                'nullable',
                'string',
                'max:255'
            ],

            'meta_description' => [
                'nullable',
                'string',
                'max:500'
            ],

            'canonical_url' => [
                'nullable',
                'url',
                'max:255'
            ],

            'keywords' => [
                'nullable',
                'array'
            ],

            'keywords.*' => [
                'string',
                'max:100'
            ],

            'published_at' => [
                'nullable',
                'date'
            ],

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],

            'is_published' => [
                'boolean'
            ],

            'is_featured' => [
                'boolean'
            ],

            'is_pinned' => [
                'boolean'
            ],

            'tags' => [
                'nullable',
                'array'
            ],

            'tags.*' => [
                'integer',
                'exists:tags,id'
            ],

        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([

            'is_published' => $this->boolean('is_published'),

            'is_featured' => $this->boolean('is_featured'),

            'is_pinned' => $this->boolean('is_pinned'),

            'keywords' => $this->prepareKeywords(),

        ]);
    }

    protected function prepareKeywords(): array
    {
        if (!$this->filled('keywords')) {
            return [];
        }

        if (is_array($this->keywords)) {
            return $this->keywords;
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $this->keywords))
        ));
    }

    public function messages(): array
    {
        return [

            'title.required' => 'عنوان مقاله الزامی است.',

            'content.required' => 'محتوای مقاله الزامی است.',

            'category_id.required' => 'دسته بندی الزامی است.',

            'category_id.exists' => 'دسته بندی معتبر نیست.',

            'featured_image.image' => 'فایل باید تصویر باشد.',

            'featured_image.max' => 'حداکثر حجم تصویر 2 مگابایت است.',

            'tags.*.exists' => 'یکی از تگ‌ها معتبر نیست.',

        ];
    }
}
