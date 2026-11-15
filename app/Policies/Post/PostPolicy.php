<?php
declare(strict_types=1);

namespace App\Policies\Post;

use App\Models\Post;
use App\Models\Doctor;

class PostPolicy
{
    public function viewAny(Doctor $Doctor): bool
    {
        return true;
    }

    public function view(Doctor $Doctor, Post $post): bool
    {
        return true;
    }

    public function create(Doctor $Doctor): bool
    {
        return $Doctor->hasRole('admin') || $Doctor->hasRole('author') || $Doctor->hasRole('doctor');
    }

    public function update(Doctor $Doctor, Post $post): bool
    {
        return $Doctor->id === $post->author_id || $Doctor->hasRole('admin');
    }

    public function delete(Doctor $Doctor, Post $post): bool
    {
        return $Doctor->id === $post->author_id || $Doctor->hasRole('admin');
    }

    public function restore(Doctor $Doctor, Post $post): bool
    {
        return $Doctor->hasRole('admin');
    }

    public function forceDelete(Doctor $Doctor, Post $post): bool
    {
        return $Doctor->hasRole('admin');
    }

    public function publish(Doctor $Doctor, Post $post): bool
    {
        return $Doctor->hasRole('admin') || $Doctor->hasRole('editor');
    }

    public function feature(Doctor $Doctor, Post $post): bool
    {
        return $Doctor->hasRole('admin');
    }

    public function pin(Doctor $Doctor, Post $post): bool
    {
        return $Doctor->hasRole('admin');
    }
}