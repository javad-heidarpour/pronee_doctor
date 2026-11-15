<?php

namespace App\Http\Controllers;

use App\Actions\Blog\Listing\BlogIndexAction;
use App\Actions\Blog\Listing\BlogListAction;
use App\Actions\Blog\Listing\DoctorBlogIndexAction;
use App\Actions\Blog\Search\SearchPostAction;
use App\Actions\Blog\Post\ShowPostAction;
use App\Actions\Blog\Tag\ShowTagAction;
use App\Actions\Blog\Comments\StoreCommentAction;

use App\Http\Requests\StoreCommentRequest;

use App\Models\Doctor;
use App\Models\Post;
use App\Models\PostComment;

use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * صفحه اصلی وبلاگ
     */
    /**
     * نظرات تایید شده مقاله
     */
    public function approvedComments(): HasMany
    {
        return $this->hasMany(PostComment::class)
            ->approved()
            ->root()
            ->latest()
            ->with([
                'replies' => function ($query) {
                    $query->approved()->oldest();
                }
            ]);
    }
    public function index(BlogIndexAction $action)
    {
        return $action->handle();
    }

    /**
     * لیست مقالات
     */
    public function blog(
        Request $request,
        BlogListAction $action
    ) {
        return $action->handle($request);
    }

    /**
     * نمایش مقاله عمومی
     */
    public function show(
        Post $post,
        ShowPostAction $action
    ) {
        return $action->handle($post);
    }

    /**
     * لیست مقالات پزشک
     */
    public function doctorBlog(
        Doctor $doctor,
        DoctorBlogIndexAction $action
    ) {
        return $action->handle($doctor);
    }

    /**
     * نمایش مقاله پزشک
     */
    public function doctorPost(
        Doctor $doctor,
        Post $post,
        ShowPostAction $action
    ) {
   
        return $action->handleDoctorPost(
            $doctor,
            $post
        );
    }

    /**
     * ثبت نظر مقاله
     */

    public function storeComment(
        StoreCommentRequest $request,
        Post $post,
        StoreCommentAction $action
    ) {
        // return response()->json([
        //     'message' => 'controller reached',
        //     'post' => $post->id,
        //     'data' => $request->all()
        // ]);


        return $action->handle(
            $request,
            $post
        );
    }

    /**
     * جستجوی مقالات
     */
    public function search(
        Request $request,
        SearchPostAction $action
    ) {
        return $action->handle($request);
    }

    /**
     * نمایش مقالات یک تگ
     */
    public function tag(
        Doctor $doctor,
        string $tag,
        ShowTagAction $action
    ) {
        return $action->handle(
            $doctor,
            $tag
        );
    }
}