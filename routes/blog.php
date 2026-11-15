<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BlogController;
use App\Models\Post;
/*
|--------------------------------------------------------------------------
| وبلاگ عمومی
|--------------------------------------------------------------------------
*/

Route::prefix('blog')->name('blog.')->group(function () {

    Route::get('/', [BlogController::class, 'blog'])->name('index');

    /* جستجو    */

    Route::get('/search', [BlogController::class, 'search'])->name('search');

    /* نمایش مقاله    */

    Route::get('/{post:slug}', [BlogController::class, 'show'])->name('show');

    /*    ثبت نظر      */
    Route::post('/{post:slug}/comment', [BlogController::class, 'storeComment'])->name('comment.store');
});

/*
|--------------------------------------------------------------------------
| صفحات اختصاصی پزشک
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------
| لیست مقالات پزشک
| مثال:
| /dr-farhad-vafaei/blog
|--------------------------------------------------------------
*/

Route::get('/{doctor:slug}/blog', [BlogController::class, 'doctorBlog'])->name('doctor.blog.index');

/*
|--------------------------------------------------------------
| نمایش مقاله پزشک
| مثال:
| /dr-farhad-vafaei/blog/child-nutrition
|--------------------------------------------------------------
*/

Route::get('/{doctor:slug}/blog/{post:slug}', [BlogController::class, 'doctorPost'])->name('doctor.blog.show');

/*
|--------------------------------------------------------------
| مقالات بر اساس تگ
| مثال:
| /dr-farhad-vafaei/tag/plastic-surgery
|--------------------------------------------------------------
*/

Route::get('/{doctor:slug}/tag/{tag}', [BlogController::class, 'tag'])->name('doctor.tag');