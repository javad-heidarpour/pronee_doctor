<?php
declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPostsAction
{
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Post::query()
            ->with([
                'doctor',
                'author',
                'category',
                'tags',
            ])
            ->withCount([
                'approvedComments as comments_count',
            ])
            ->withAvg([
                'approvedComments as average_rating',
            ], 'rating');

        // فیلتر جستجو
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // فیلتر دسته‌بندی
        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        // فیلتر پزشک
        if (!empty($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        // فیلتر وضعیت
        if (isset($filters['status'])) {
            if ($filters['status'] === 'published') {
                $query->published();
            } elseif ($filters['status'] === 'draft') {
                $query->unpublished();
            }
        }

        // فیلتر ویژه
        if (!empty($filters['is_featured'])) {
            $query->featured();
        }

        // فیلتر پین شده
        if (!empty($filters['is_pinned'])) {
            $query->pinned();
        }



        // return $query->latestPublished()
        return $query->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }
}