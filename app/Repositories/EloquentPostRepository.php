<?php
// app/Repositories/EloquentPostRepository.php
namespace App\Repositories;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Filters\PostFilter;

class EloquentPostRepository implements PostRepositoryInterface
{
    public function getAll(PostFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = Post::query()->withBlogRelations()->withCommentStatistics();
        
        return $filter->apply($query)->latest()->paginate($perPage);
    }

    public function find(int $id): ?Post
    {
        return Post::withBlogRelations()->find($id);
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }

    public function update(Post $post, array $data): bool
    {
        return $post->update($data);
    }

    public function delete(Post $post): bool
    {
        return $post->delete();
    }
}