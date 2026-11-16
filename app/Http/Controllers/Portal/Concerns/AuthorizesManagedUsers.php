<?php

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait AuthorizesManagedUsers
{
    use HasActiveDoctor;

    /**
     * Admins can manage all users; doctors can manage only their own secretaries.
     */
    protected function authorizeUserManagement(?User $target = null): ?Doctor
    {
        $authUser = Auth::guard('web')->user();

        abort_unless(
            $authUser && in_array($authUser->role, ['doctor', 'admin'], true),
            403,
            'دسترسی به مدیریت کاربران مجاز نیست.'
        );

        if ($authUser->role === 'admin') {
            return null;
        }

        $doctor = $this->currentDoctor();

        if ($target) {
            abort_unless(
                $target->role === 'secretary' &&
                $target->doctors()->whereKey($doctor->id)->exists(),
                403,
                'این منشی به پرونده پزشک فعال شما متصل نیست.'
            );
        }

        return $doctor;
    }

    protected function preventSelfModification(User $user): void
    {
        abort_if(
            (int) Auth::guard('web')->id() === (int) $user->id,
            403,
            'امکان تغییر حساب کاربری خودتان از این بخش وجود ندارد.'
        );
    }
}
