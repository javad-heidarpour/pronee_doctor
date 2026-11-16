<?php

namespace App\Actions\Portal\Users;

class ListManagedUserPermissionsAction
{
    public function handle(): array
    {
        return [
            'appointments.view' => 'مشاهده نوبت‌ها',
            'appointments.manage' => 'مدیریت نوبت‌ها',
            'patients.view' => 'مشاهده بیماران',
            'patients.manage' => 'مدیریت بیماران',
            'clinics.view' => 'مشاهده مطب‌ها',
            'clinics.manage' => 'مدیریت مطب‌ها',
            'shifts.view' => 'مشاهده شیفت‌ها',
            'shifts.manage' => 'مدیریت شیفت‌ها',
            'working-hours.view' => 'مشاهده ساعات کاری',
            'working-hours.manage' => 'مدیریت ساعات کاری',
        ];
    }
}
