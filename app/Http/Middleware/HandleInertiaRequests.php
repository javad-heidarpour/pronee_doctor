<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;

class HandleInertiaRequests extends Middleware
{
    private function getCurrentDoctor($user)
    {
        if (!$user) {
            return null;
        }

        $doctorId = session('active_doctor_id');

        if (!$doctorId) {
            return null;
        }

        // Resolve through the same role-aware access contract used by the portal.
        // Doctors own their profile through doctors.user_id; secretaries use doctor_user;
        // administrators may select any active doctor. The raw doctors() relation is
        // only the secretary pivot and therefore returns null for doctors/admins.
        return $user->accessibleDoctors()
            ->whereKey($doctorId)
            ->first();
    }
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            /*
            |--------------------------------------------------------------------------
            | Auth
            |--------------------------------------------------------------------------
            */

            'auth' => [
                'user' => fn() => Auth::guard('web')->user()?->only([
                    'id',
                    'name',
                    'family',
                    'email',
                    'role',
                ]),

                'doctor' => fn() => $this->getCurrentDoctor(
                    Auth::guard('web')->user()
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Menus
            |--------------------------------------------------------------------------
            */

            'menus' => fn() => $this->getUserMenus(),

            /*
            |--------------------------------------------------------------------------
            | Ziggy
            |--------------------------------------------------------------------------
            */

            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Flash Messages
            |--------------------------------------------------------------------------
            */

            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'appointment_id' => fn() => $request->session()->get('appointment_id'),
                'payment_url' => fn() => $request->session()->get('payment_url'),
                'info' => fn() => $request->session()->get('info'),
            ],
        ];
    }

    /**
     * اطلاعات پزشک فعال کاربر
     */

    /**
     * منوهای متناسب با نقش کاربر
     */
    private function getUserMenus()
    {
        $user = Auth::guard('web')->user();

        if (!$user) {
            return [];
        }


        $guard = match ($user->role) {
            'doctor', 'secretary', 'admin' => 'doctor',
            default => null,
        };

        if (!$guard) {
            return [];
        }

        $doctorId = session('active_doctor_id');
        // dd( $doctorId );
        return Menu::forGuard($guard)
            ->get()
            ->filter(fn($menu) => $menu->hasPermission($user, $doctorId))
            ->map(fn($menu) => [
                'id' => $menu->id,
                'title' => $menu->title,
                'route' => $menu->route_name,
                'url' => $menu->url,
                'full_url' => $menu->full_url,
                'icon' => $menu->icon,
                'order' => $menu->order,
            ])
            ->values();
    }
}