<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        \Log::info([
            'featured_image' => $this->input('featured_image'),
            'hasFile' => $this->hasFile('featured_image'),
            'all' => $this->all(),
        ]);

        return [
            'title' => 'required|string|max:255|min:5',
            'slug' => 'required|string|max:255|unique:posts,slug,' . $this->route('post')->id,
            'content' => 'required|string|min:20',
            'excerpt' => 'required|string|min:10',
            'category_id' => 'required|exists:categories,id',
            'doctor_id' => 'nullable|exists:doctors,id',

            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            // 'featured_image' => 'nullable|image|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',

            'keywords' => 'nullable|array',

            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'is_pinned' => 'boolean',
        ];
    }

       public function messages(): array
    {
        return [
            // عنوان
            'title.required' => 'عنوان مقاله الزامی است.',
            'title.max'      => 'عنوان مقاله نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',

            // نامک (Slug)
            'slug.required' => 'نامک (Slug) الزامی است.',
            'slug.unique'   => 'این نامک قبلاً استفاده شده است، لطفاً نامک دیگری انتخاب کنید.',

            // محتوا و چکیده
            'content.required'  => 'محتوای اصلی مقاله الزامی است.',
            'excerpt.required'  => 'چکیده مقاله الزامی است.',

            // دسته‌بندی و نویسنده (پزشک)
            'category_id.required' => 'انتخاب دسته‌بندی الزامی است.',
            'category_id.exists'   => 'دسته‌بندی انتخاب‌شده معتبر نیست.',
            'doctor_id.exists'     => 'پزشک (نویسنده) انتخاب‌شده معتبر نیست.',

            // تصویر شاخص (برای زمانی که کامنت را بردارید)
            'featured_image.image' => 'فایل آپلود شده باید یک تصویر معتبر (jpeg,png,jpg,gif,webp) باشد.',
            'featured_image.max'   => 'حجم تصویر نباید بیشتر از ۵ مگابایت باشد.',

            // سئو و کلمات کلیدی
            'meta_title.max' => 'عنوان سئو نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'keywords.array' => 'فرمت کلمات کلیدی ارسال‌شده نامعتبر است.',

            // مقادیر بولی (چک‌باکس‌ها)
            'is_published.boolean' => 'مقدار وضعیت انتشار نامعتبر است.',
            'is_featured.boolean'  => 'مقدار وضعیت ویژه بودن نامعتبر است.',
            'is_pinned.boolean'    => 'مقدار وضعیت پین شدن نامعتبر است.',
        ];
    }
}