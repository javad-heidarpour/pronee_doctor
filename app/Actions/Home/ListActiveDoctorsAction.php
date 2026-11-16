<?php

namespace App\Actions\Home;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Collection;

class ListActiveDoctorsAction
{
    public function handle(): Collection
    {
        return Doctor::query()
            ->where('is_active', 1)
            ->select([
                'id',
                'slug',
                'caption',
                'name',
                'family',
                'specialty_title',
                'photo',
            ])
            ->orderBy('id')
            ->get();
    }
}
