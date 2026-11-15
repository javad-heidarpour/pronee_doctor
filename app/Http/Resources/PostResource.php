<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'title' => $this->title,

            'slug' => $this->slug,

            'excerpt' => $this->excerpt,

            'content' => $this->content,

            'featured_image' => $this->featured_image,

            'featured_image_url' => $this->featured_image_url,

            'meta_title' => $this->meta_title,

            'meta_description' => $this->meta_description,

            'canonical_url' => $this->canonical_url,

            'keywords' => $this->keywords,

            'views_count' => $this->views_count,

            'reading_time' => $this->reading_time,

            'published_at' => $this->published_at,

            'persian_published_at' => $this->persian_published_at,

            'average_rating' => $this->average_rating,

            'comments_count' => $this->comments_count,

            'is_featured' => $this->is_featured,

            'is_pinned' => $this->is_pinned,

            'is_published' => $this->is_published,

            'doctor' => DoctorResource::make(
                $this->whenLoaded('doctor')
            ),

            'author' => UserResource::make(
                $this->whenLoaded('author')
            ),

            'category' => CategoryResource::make(
                $this->whenLoaded('category')
            ),

            'tags' => TagResource::collection(
                $this->whenLoaded('tags')
            ),

        ];
    }
}