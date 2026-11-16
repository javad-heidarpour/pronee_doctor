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
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }
}
