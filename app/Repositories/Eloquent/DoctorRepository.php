<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Doctor;
use App\Repositories\Contracts\DoctorRepositoryInterface;

class DoctorRepository implements DoctorRepositoryInterface
{
    public function findById(int $id): ?Doctor
    {
        return Doctor::query()->find($id);
    }

    public function update(Doctor $doctor, array $attributes): Doctor
    {
        $doctor->fill($attributes);
        $doctor->save();

        return $doctor->fresh();
    }

    public function delete(Doctor $doctor): bool
    {
        return (bool) $doctor->delete();
    }
}