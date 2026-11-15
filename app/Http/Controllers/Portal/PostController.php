<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\DTOs\Post\PostData;
use App\Filters\Post\PostFilter;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\Doctor; // اگر نویسندگان از جدول users هستند، این را به User تغییر دهید
use App\Services\Post\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log; // این خط را بالای فایل اضافه کنید
use App\Http\Requests\Portal\Post\UpdatePostRequest;
use App\Http\Requests\Portal\Post\TestRequest;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function __construct(
        protected PostService $postService
    ) {
        // $this->authorizeResource(Post::class, 'post');
    }
    public function store(Request $request): RedirectResponse
    {
        // ۱. ولیدیشن مستقیم و دقیق
        $validated = $request->validate([
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
        ], [
            'title.required' => 'عنوان مقاله الزامی است.',
            'slug.required' => 'نامک (Slug) الزامی است.',
            'slug.unique' => 'این نامک قبلاً استفاده شده است.',
            'content.required' => 'محتوای مقاله الزامی است.',
            'category_id.required' => 'انتخاب دسته‌بندی الزامی است.',
            'featured_image.image' => 'فایل باید تصویر باشد.',
            'featured_image.max' => 'حجم تصویر حداکثر ۵ مگابایت است.',
        ]);

        // ۲. تولید خودکار خلاصه اگر خالی باشد (بهبود یافته با mb_strimwidth برای پشتیبانی بهتر از فارسی)
        if (empty($validated['excerpt']) && !empty($validated['content'])) {
            $plainText = strip_tags($validated['content']);
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $plainText = trim($plainText);

            // mb_strimwidth به صورت خودکار در صورت برش، سه نقطه (...) اضافه می‌کند
            $validated['excerpt'] = mb_strimwidth($plainText, 0, 150, '...');
        }

        // ۳. مدیریت هوشمند تصویر (با الگوبرداری از متد update و اضافه کردن try-catch)
        if ($request->hasFile('featured_image')) {
            try {
                $validated['featured_image'] = $request->file('featured_image')->store('posts', 'public');
            } catch (\Exception $e) {
                \Log::error('خطا در آپلود تصویر (هنگام ایجاد مقاله)', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => auth()->id(), // برای ردیابی بهتر خطا
                ]);

                // در صورت بروز خطا، عملیات ایجاد مقاله متوقف نمی‌شود و تصویر null در نظر گرفته می‌شود
                $validated['featured_image'] = null;
            }
        }

        // ۴. مدیریت تاریخ انتشار
        if (!empty($validated['is_published'])) {
            $validated['published_at'] = now();
        }

        // ۵. ایجاد مقاله
        // نکته: مطمئن شوید در مدل Post، فیلد keywords به صورت 'array' کست شده باشد:
        // protected $casts = ['keywords' => 'array'];
        Post::create($validated);

        return redirect()
            ->route('portal.posts.index')
            ->with('success', 'مقاله با موفقیت ایجاد شد.');
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $validated = $request->validated();

        // مدیریت هوشمند تصویر
        if ($request->hasFile('featured_image')) {
            try {
                // حذف تصویر قدیمی (اگر وجود دارد)
                if ($post->featured_image) {
                    $oldPath = $post->featured_image;
                    if (\Storage::disk('public')->exists($oldPath)) {
                        \Storage::disk('public')->delete($oldPath);
                    }
                }

                // ذخیره تصویر جدید
                $validated['featured_image'] = $request->file('featured_image')->store('posts', 'public');
            } catch (\Exception $e) {
                \Log::error('خطا در آپلود تصویر', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                // اگر آپلود ناموفق بود، این فیلد را از آپدیت حذف کن تا تصویر قبلی حفظ شود
                unset($validated['featured_image']);
            }
        } else {
            // ==========================================================
            // <<< تغییر اصلی اینجاست >>>
            // اگر فایل جدیدی آپلود نشده، این کلید را از آرایه حذف کن
            // تا Laravel آن را در کوئری UPDATE دیتابیس قرار ندهد و تصویر قبلی null نشود.
            unset($validated['featured_image']);
            // ==========================================================
        }

        if (!empty($validated['is_published']) && !$post->published_at) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        return redirect()
            ->route('portal.posts.index')
            ->with('success', 'مقاله با موفقیت ویرایش شد.');
    }
    public function index(Request $request)
    {
        $filter = new PostFilter($request);

        // دریافت پست‌ها و لود کردن روابط برای جلوگیری از خطای N+1
        $posts = $this->postService->getAllPosts($filter->toArray());
        $posts->load(['author', 'category']);

        // بهینه‌سازی: فقط فیلدهای مورد نیاز برای دراپ‌داون‌ها دریافت شوند
        $categories = Category::select('id', 'name')->get();
        $authors = Doctor::select('id', 'name')->get();
        $user = Auth::guard('web')->user();
        $doctor = $user->doctorProfile;

        return Inertia::render('Portal/Posts/Index', [
            'doctor' => $doctor,
            'posts' => $posts,
            'categories' => $categories,
            'authors' => $authors,
        ]);
    }

    public function create()
    {
        
        // هماهنگ‌سازی با Inertia (به جای view از Inertia::render استفاده می‌کنیم)
        return Inertia::render('Portal/Posts/Create', [
            'categories' => Category::select('id', 'name')->get(),
            'authors' => Doctor::select('id', 'name')->get(),
        ]);
    }

    public function show(Post $post)
    {
        // افزایش بازدید
        $this->postService->incrementViews($post);

        // لود کردن روابط برای جلوگیری از خطای N+1 در Vue
        $post->load(['author', 'category', 'tags']);

        return Inertia::render('Portal/Posts/Show', [
            'post' => $post,
        ]);
    }

    public function edit(Post $post)
    {
        return Inertia::render('Portal/Posts/Edit', [
            'post' => $post->load(['category', 'author']),
            'categories' => Category::select('id', 'name')->get(),
            'authors' => Doctor::select('id', 'name')->get(),
        ]);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->postService->delete($post);

        return redirect()
            ->route('portal.posts.index') // اصلاح نام روت
            ->with('success', 'مقاله به سطل زباله منتقل شد.');
    }

    // ---------------------------------------------------------
    // متدهای اضافی هماهنگ با روت‌ها
    // ---------------------------------------------------------

    public function restore(string $slug): RedirectResponse
    {
        $post = Post::withTrashed()->where('slug', $slug)->firstOrFail();
        $this->postService->restore($post);

        return redirect()
            ->route('portal.posts.index') // اصلاح نام روت
            ->with('success', 'مقاله با موفقیت بازیابی شد.');
    }

    public function forceDelete(string $slug): RedirectResponse
    {
        $post = Post::withTrashed()->where('slug', $slug)->firstOrFail();
        $this->postService->forceDelete($post);

        return redirect()
            ->route('portal.posts.index') // اصلاح نام روت
            ->with('success', 'مقاله به صورت دائمی حذف شد.');
    }

    public function toggleFeatured(Post $post): RedirectResponse
    {
        $this->postService->toggleFeatured($post);
        return back()->with('success', 'وضعیت ویژه مقاله تغییر کرد.');
    }

    public function togglePin(Post $post): RedirectResponse
    {
        $this->postService->togglePin($post);
        return back()->with('success', 'وضعیت پین مقاله تغییر کرد.');
    }
}