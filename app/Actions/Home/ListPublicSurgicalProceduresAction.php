<?php

namespace App\Actions\Home;

use App\Models\SurgicalProcedure;
use Illuminate\Database\Eloquent\Collection;

class ListPublicSurgicalProceduresAction
{
    public function handle(): Collection
    {
        return SurgicalProcedure::query()
            ->where('is_active', true)
            ->select(['id', 'title', 'category', 'short_description', 'intro_title'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }
}
