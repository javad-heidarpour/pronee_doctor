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
            'patients.clinical.view' => 'مشاهده اطلاعات پزشکی محرمانه',
            'patients.clinical.manage' => 'ثبت و ویرایش اطلاعات پزشکی',
            'clinics.view' => 'مشاهده مطب‌ها',
            'shifts.view' => 'مشاهده شیفت‌ها',
            'shifts.manage' => 'مدیریت شیفت‌ها',
            'working-hours.view' => 'مشاهده ساعات کاری',
            'working-hours.manage' => 'مدیریت ساعات کاری',
            'finance.view' => 'مشاهده امور مالی و اقساط',
            'finance.manage' => 'ثبت دریافت، اقساط، وثایق و هزینه‌ها',
            'services.view' => 'مشاهده خدمات و عملیات کلینیک',
            'services.manage' => 'مدیریت خدمات کلینیک',
            'inventory.view' => 'مشاهده انبار و موجودی',
            'inventory.manage' => 'مدیریت کالا و گردش انبار',
            'organizations.view' => 'مشاهده مجموعه درمانی',
            'staff.attendance.view' => 'ثبت و مشاهده حضور و غیاب',
        ];
    }
}
