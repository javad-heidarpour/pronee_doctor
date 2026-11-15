<?php
declare(strict_types=1);

namespace App\Services\Post;

use App\Actions\Portal\Post\CreatePostAction;
use App\Actions\Portal\Post\DeletePostAction;
use App\Actions\Portal\Post\FeaturePostAction;
use App\Actions\Portal\Post\FeaturedPostsAction;
use App\Actions\Portal\Post\ForceDeletePostAction;
use App\Actions\Portal\Post\IndexPostsAction;
use App\Actions\Portal\Post\IncrementPostViewsAction;
use App\Actions\Portal\Post\LatestPostsAction;
use App\Actions\Portal\Post\PinPostAction;
use App\Actions\Portal\Post\PinnedPostsAction;
use App\Actions\Portal\Post\RestorePostAction;
use App\Actions\Portal\Post\ShowPostAction;
use App\Actions\Portal\Post\ToggleFeaturedPostAction;
use App\Actions\Portal\Post\UpdatePostAction;
use App\DTOs\Post\PostData;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PostService
{
    public function __construct(
        protected CreatePostAction $create,
        protected UpdatePostAction $update,
        protected DeletePostAction $delete,
        protected ForceDeletePostAction $forceDelete,
        protected RestorePostAction $restore,
        protected FeaturePostAction $feature,
        protected ToggleFeaturedPostAction $toggleFeatured,
        protected PinPostAction $pin,
        protected ShowPostAction $show,
        protected IndexPostsAction $index,
        protected LatestPostsAction $latest,
        protected FeaturedPostsAction $featured,
        protected PinnedPostsAction $pinned,
        protected IncrementPostViewsAction $incrementViews,
        protected PostCacheService $cache
    ) {
    }

    // لیست مقالات با فیلتر
    public function getAllPosts(array $filters = []): LengthAwarePaginator
    {
        return $this->index->execute($filters);
    }

    // نمایش یک مقاله
    public function getPost(Post $post): Post
    {
        return $this->show->execute($post);
    }

    // ایجاد مقاله
    public function createPost(PostData $data): Post
    {
        $post = $this->create->execute($data->toArray());
        $this->cache->forgetAll();
        return $post;
    }

    // ویرایش مقاله
    public function update(Post $post, PostData $data): Post
    {
        \Log::info('Service Update Started');
        // ۱. مدیریت آپلود تصویر جدید
        if ($data->featured_image instanceof \Illuminate\Http\UploadedFile) {

            // حذف تصویر قدیمی
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }


            // ذخیره تصویر جدید
            $newImagePath = $data->featured_image->store('posts', 'public');


            $post->featured_image = $newImagePath;
        }


        $updateData = $data->toArray();


        $post->update($updateData);


        if (isset($newImagePath)) {
            $post->save();
        }

        \Log::info('Service Update Finished');
        return $post->fresh();
    }

    // حذف نرم مقاله
    public function deletePost(Post $post): void
    {
        $this->delete->execute($post);
        $this->cache->forgetAll();
    }

    // حذف دائمی مقاله
    public function forceDeletePost(Post $post): void
    {
        $this->forceDelete->execute($post);
        $this->cache->forgetAll();
    }

    // بازیابی مقاله حذف شده
    public function restorePost(Post $post): Post
    {
        $post = $this->restore->execute($post);
        $this->cache->forgetAll();
        return $post;
    }

    // ویژه کردن مقاله
    public function featurePost(Post $post): Post
    {
        $post = $this->feature->execute($post);
        $this->cache->forgetAll();
        return $post;
    }

    // تغییر وضعیت ویژه
    public function toggleFeaturedPost(Post $post): Post
    {
        $post = $this->toggleFeatured->execute($post);
        $this->cache->forgetAll();
        return $post;
    }

    // پین کردن مقاله
    public function pinPost(Post $post): Post
    {
        $post = $this->pin->execute($post);
        $this->cache->forgetAll();
        return $post;
    }

    // افزایش بازدید
    public function incrementViews(Post $post): void
    {
        $this->incrementViews->execute($post);
    }

    // مقالات ویژه
    public function getFeaturedPosts(int $limit = 6): Collection
    {
        return $this->featured->execute($limit);
    }

    // مقالات پین شده
    public function getPinnedPosts(int $limit = 5): Collection
    {
        return $this->pinned->execute($limit);
    }

    // آخرین مقالات
    public function getLatestPosts(int $limit = 10): Collection
    {
        return $this->latest->execute($limit);
    }
}