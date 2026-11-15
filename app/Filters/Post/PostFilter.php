<?php
declare(strict_types=1);

namespace App\Filters\Post;

use Illuminate\Http\Request;

class PostFilter
{
    public function __construct(
        protected Request $request
    ) {}

    public function toArray(): array
    {
        return [
            'search' => $this->request->input('search'),
            'category_id' => $this->request->input('category_id'),
            'doctor_id' => $this->request->input('doctor_id'),
            'status' => $this->request->input('status'),
            'is_featured' => $this->request->boolean('is_featured'),
            'is_pinned' => $this->request->boolean('is_pinned'),
            'per_page' => $this->request->input('per_page', 15),
        ];
    }
}