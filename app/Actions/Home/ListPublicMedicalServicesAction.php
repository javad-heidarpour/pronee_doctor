<?php

namespace App\Actions\Home;

use App\Models\MedicalService;
use Illuminate\Database\Eloquent\Collection;

class ListPublicMedicalServicesAction
{
    public function handle(): Collection
    {
        return MedicalService::query()
            ->where('is_active', true)
            ->select(['id', 'title', 'category', 'short_description'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }
}
