<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Doctor;

interface DoctorRepositoryInterface
{
    public function findById(int $id): ?Doctor;

    public function update(Doctor $doctor, array $attributes): Doctor;

    public function delete(Doctor $doctor): bool;
}