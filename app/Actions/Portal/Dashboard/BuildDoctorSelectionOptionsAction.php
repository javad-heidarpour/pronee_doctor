<?php

namespace App\Actions\Portal\Dashboard;

use Illuminate\Database\Eloquent\Collection;

class BuildDoctorSelectionOptionsAction
{
    public function handle(Collection $doctors): array
    {
        return $doctors->map(fn ($doctor) => [
            'id' => $doctor->id,
            'name' => $doctor->name,
            'family' => $doctor->family,
            'caption' => $doctor->caption,
            'photo' => $doctor->photo,
            'specialty_title' => $doctor->specialty_title,
        ])->values()->all();
    }
}
