<?php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Filters\PostFilter;

interface PostRepositoryInterface
{
    public function getAll(PostFilter $filter, int $perPage = 15): LengthAwarePaginator;
    public function find(int $id): ?Post;
    public function create(array $data): Post;
    public function update(Post $post, array $data): bool;
    public function delete(Post $post): bool;
}