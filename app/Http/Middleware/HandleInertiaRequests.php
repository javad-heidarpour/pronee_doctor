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

        return $user->doctors()
            ->where('doctors.id', $doctorId)
            ->where('doctors.is_active', true)
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