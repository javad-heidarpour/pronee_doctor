<?php

declare(strict_types=1);

namespace App\Policies\Portal;

use App\Models\Doctor;

class DoctorPolicy
{
    /**
     * مشاهده پروفایل
     */
    public function view(Doctor $doctor, Doctor $model): bool
    {
        return $doctor->id === $model->id;
    }

    /**
     * بروزرسانی پروفایل
     */
    public function update(Doctor $doctor, Doctor $model): bool
    {
        return $doctor->id === $model->id;
    }

    /**
     * حذف حساب
     */
    public function delete(Doctor $doctor, Doctor $model): bool
    {
        return $doctor->id === $model->id;
    }
}