<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PostController;
// اگر کنترلرهای جداگانه دارید، آن‌ها را اینجا ایمپورت کنید:
// use App\Http\Controllers\Portal\CategoryController;
// use App\Http\Controllers\Portal\TagController;
// use App\Http\Controllers\Portal\AuthorController;

// ==========================================
// مدیریت مقالات (Posts)
// ==========================================
Route::prefix('posts')->name('posts.')->group(function () {

    Route::post('/debug-upload', function (\Illuminate\Http\Request $request) {
        dd(
        'آیا فایل وجود دارد؟: ' . ($request->hasFile('featured_image') ? 'بله ✅' : 'خیر ❌'),
        'جزئیات فایل:',
        $request->file('featured_image'),
        'همه داده‌های ارسالی:',
        $request->all()
    );
    })->name('update-test');

    Route::get('/test-request', function () {
        try {
            $request = new \App\Http\Requests\Portal\Post\UpdatePostRequest();
            $rules = $request->rules();

            return response()->json([
                'success' => true,
                'rules_count' => count($rules),
                'rules' => $rules
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    });

    // ۱. روت‌های ثابت (بدون متغیر) -> باید حتماً اول باشند
    Route::get('/', [PostController::class, 'index'])->name('index');
    Route::get('/create', [PostController::class, 'create'])->name('create');
    Route::post('/', [PostController::class, 'store'])->name('store');

    // ۲. روت‌های عملیاتی با ID -> باید قبل از slug باشند
    Route::get('/{post:id}/edit', [PostController::class, 'edit'])->name('edit');

    // Route::get('/{post}/edit', function () {
    //     return 1;
    // })->name('edit');

    Route::post('/{post:id}', [PostController::class, 'update'])->name('update');
    Route::delete('/{post:id}', [PostController::class, 'destroy'])->name('destroy');
    Route::patch('/{post:id}/toggle-pin', [PostController::class, 'togglePin'])->name('toggle-pin');
    Route::patch('/{post:id}/toggle-featured', [PostController::class, 'toggleFeatured'])->name('toggle-featured');

    // ۳. روت‌های با متغیر Slug -> باید حتماً آخر باشند (Catch-all)
    Route::get('/{post:slug}', [PostController::class, 'show'])->name('show');
});


// ==========================================
// سایر روت‌ها (Categories, Tags, Authors)
// ==========================================
// نکته: بهتر است برای این‌ها کنترلرهای جداگانه بسازید، اما اگر فعلاً در PostController هستند:

Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [PostController::class, 'categoriesIndex'])->name('index');
    Route::post('/', [PostController::class, 'storeCategory'])->name('store');
    Route::put('/{category}', [PostController::class, 'updateCategory'])->name('update');
    Route::delete('/{category}', [PostController::class, 'destroyCategory'])->name('destroy');
});

Route::prefix('tags')->name('tags.')->group(function () {
    Route::get('/', [PostController::class, 'tagsIndex'])->name('index');
    Route::post('/', [PostController::class, 'storeTag'])->name('store');
    Route::delete('/{tag}', [PostController::class, 'destroyTag'])->name('destroy');
});

Route::prefix('authors')->name('authors.')->group(function () {
    Route::get('/', [PostController::class, 'authorsIndex'])->name('index');
});