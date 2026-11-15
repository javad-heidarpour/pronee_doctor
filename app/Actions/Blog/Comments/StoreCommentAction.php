<?php

namespace App\Actions\Blog\Comments;

use App\Http\Requests\StoreCommentRequest;
use App\Models\PostComment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class StoreCommentAction
{
    /**
     * ثبت نظر برای مقاله
     */
    public function handle(
        StoreCommentRequest $request,
        Post $post
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | جلوگیری از ارسال پشت سرهم (۲ دقیقه)
        |--------------------------------------------------------------------------
        */
        $recentComment = PostComment::query()
            ->where('post_id', $post->id)
            ->where('ip_address', $request->ip())
            ->where('created_at', '>=', Carbon::now()->subMinutes(2))
            ->exists();

        if ($recentComment) {

            return back()->withErrors([
                'comment' => 'شما به‌تازگی برای این مقاله نظر ثبت کرده‌اید. لطفاً چند دقیقه بعد دوباره تلاش کنید.',
            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | جلوگیری از ثبت نظر تکراری
        |--------------------------------------------------------------------------
        */

        $duplicateComment = PostComment::query()
            ->where('post_id', $post->id)
            ->whereRaw(
                'LOWER(comment) = ?',
                [mb_strtolower(trim($request->comment))]
            )
            ->exists();

        if ($duplicateComment) {

            return back()->withErrors([
                'comment' => 'این نظر قبلاً ثبت شده است.',
            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | محدودیت تعداد نظرات هر IP در یک ساعت
        |--------------------------------------------------------------------------
        */

        $hourlyLimit = PostComment::query()
            ->where('ip_address', $request->ip())
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();

        if ($hourlyLimit >= 10) {

            return back()->withErrors([
                'comment' => 'تعداد ارسال نظر شما بیش از حد مجاز است. لطفاً بعداً دوباره تلاش کنید.',
            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | ثبت نظر
        |--------------------------------------------------------------------------
        */

        PostComment::create([

            'post_id' => $post->id,

            'user_id' => auth()->id(),

            'parent_id' => $request->parent_id,

            'name' => trim($request->name),

            'email' => trim($request->email),

            'patient_name' => trim($request->patient_name),

            'comment' => trim($request->comment),

            'rating' => (int) $request->rating,

            'is_anonymous' => (bool) $request->boolean('is_anonymous'),

            // پس از تایید مدیر نمایش داده می‌شود
            'is_approved' => false,

            'approved_at' => null,

            'approved_by' => null,

            'ip_address' => $request->ip(),

            'user_agent' => substr(
                (string) $request->userAgent(),
                0,
                255
            ),

            'likes_count' => 0,

            'dislikes_count' => 0,

            'is_edited' => false,

            'edited_at' => null,

        ]);

        /*
        |--------------------------------------------------------------------------
        | پیام موفقیت
        |--------------------------------------------------------------------------
        */

        return back()->with(
            'success',
            'نظر شما با موفقیت ثبت شد و پس از بررسی مدیر منتشر خواهد شد.'
        );
    }
}