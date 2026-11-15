<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    /**
     * Available user roles.
     */
    private function availablePermissions(): array
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
public function assignments(User $user): Response
{
    $authUser = Auth::guard('web')->user();

    abort_unless(
        $authUser &&
        in_array($authUser->role, ['doctor', 'admin'], true),
        403
    );

    abort_unless($user->role === 'secretary', 404);

    // دریافت پزشکان از جدول doctors
    $doctorsQuery = Doctor::query()
        ->with('user')
        ->whereHas('user', function ($query) {
            $query->where('role', 'doctor')
                ->where('is_active', true);
        });

    // پزشک فقط پرونده پزشکی متعلق به حساب خودش را می‌بیند.
    if ($authUser->role === 'doctor') {
        $doctorsQuery->where('user_id', $authUser->id);
    }

    $doctors = $doctorsQuery
        ->orderBy('name')
        ->get();

    // پزشکان قبلاً متصل‌شده به منشی
    $assigned = $user->doctors()
        ->get()
        ->keyBy('id');

    return Inertia::render('Portal/Users/Assignments', [
        'doctor' => $authUser->doctorProfile,

        'user' => $user->only([
            'id',
            'name',
            'family',
            'phone',
            'role',
        ]),

        'doctors' => $doctors->map(function ($doctor) use ($assigned) {
            $relation = $assigned->get($doctor->id);

            $permissions = $relation?->pivot?->permissions ?? [];

            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true) ?: [];
            }

            return [
                'id' => $doctor->id,
                'name' => $doctor->name ?: $doctor->user?->name,
                'family' => $doctor->family ?: $doctor->user?->family,
                'phone' => $doctor->phone ?: $doctor->user?->phone,
                'specialty_title' => $doctor->specialty_title,
                'assigned' => (bool) $relation,
                'permissions' => $permissions,
            ];
        })->values(),

        'permissions' => $this->availablePermissions(),
    ]);
}
public function updateAssignments(Request $request, User $user)
{
    $authUser = Auth::guard('web')->user();

    abort_unless(
        $authUser &&
        in_array($authUser->role, ['doctor', 'admin'], true),
        403
    );

    abort_unless($user->role === 'secretary', 404);

    $availablePermissions = array_keys(
        $this->availablePermissions()
    );

    $validated = $request->validate([
        'doctors' => ['present', 'array'],

        'doctors.*.id' => [
            'required',
            'integer',
            'distinct',
            Rule::exists('doctors', 'id'),
        ],

        'doctors.*.permissions' => [
            'required',
            'array',
        ],

        'doctors.*.permissions.*' => [
            'required',
            'string',
            'distinct',
            Rule::in($availablePermissions),
        ],
    ]);

    $doctorIds = collect($validated['doctors'])
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    // دریافت پرونده‌های پزشکان از جدول doctors
    $doctors = Doctor::query()
        ->with('user')
        ->whereIn('id', $doctorIds)
        ->get()
        ->keyBy('id');

    // بررسی فعال بودن حساب کاربری تمام پزشکان
    foreach ($doctorIds as $doctorId) {
        $doctor = $doctors->get($doctorId);

        abort_unless(
            $doctor &&
            $doctor->user &&
            $doctor->user->role === 'doctor' &&
            $doctor->user->is_active,
            422,
            'یکی از پزشکان انتخاب‌شده معتبر یا فعال نیست.'
        );

        // پزشک فقط می‌تواند منشی را به پرونده خودش متصل کند.
        if ($authUser->role === 'doctor') {
            abort_unless(
                (int) $doctor->user_id === (int) $authUser->id,
                403,
                'شما فقط مجاز به مدیریت ارتباط با پرونده پزشکی خودتان هستید.'
            );
        }
    }

    $syncData = [];

    foreach ($validated['doctors'] as $item) {
        $syncData[$item['id']] = [
            'permissions' => json_encode(
                $item['permissions'],
                JSON_UNESCAPED_UNICODE
            ),
        ];
    }

    DB::transaction(function () use ($user, $syncData) {
        $user->doctors()->sync($syncData);
    });

    return redirect()
        ->route('portal.users.assignments', $user->id)
        ->with(
            'success',
            'ارتباط پزشکان و سطح دسترسی منشی با موفقیت ذخیره شد.'
        );
}
    private function roles(): array
    {
        return [
            [
                'value' => 'doctor',
                'label' => 'پزشک',
                'icon' => '⚕️',
                'description' => 'دسترسی به پنل پزشک',
            ],
            [
                'value' => 'secretary',
                'label' => 'منشی',
                'icon' => '🗂️',
                'description' => 'مدیریت نوبت‌ها و مراجعان',
            ],
            [
                'value' => 'admin',
                'label' => 'مدیر',
                'icon' => '🛡️',
                'description' => 'مدیریت سیستم',
            ],
            [
                'value' => 'user',
                'label' => 'کاربر عادی',
                'icon' => '👤',
                'description' => 'دسترسی عمومی',
            ],
        ];
    }

    /**
     * Validate user data.
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],

            'family' => ['required', 'string', 'max:100'],

            'phone' => [
                'required',
                'string',
                'max:11',
                Rule::unique('users', 'phone')->ignore($user?->id),
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],

            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],

            'password' => $user
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],

            'role' => [
                'required',
                Rule::in(['doctor', 'secretary', 'admin', 'user']),
            ],

            'is_active' => ['required', 'boolean'],
        ]);
    }

    /**
     * Prevent modifying authenticated user's own account.
     */
    private function preventSelfModification(User $user): void
    {
        abort_if(
            (int) Auth::guard('web')->id() === (int) $user->id,
            403,
            'امکان تغییر حساب کاربری خودتان از این بخش وجود ندارد.'
        );
    }

    /**
     * Hash password when provided.
     */
    private function hashPassword(array $validated): array
    {
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        return $validated;
    }

    /**
     * Users list.
     */
    public function index(): Response
    {
        $user = Auth::guard('web')->user();
        // dd($user?->doctorProfile);
        return Inertia::render('Portal/Users/Index', [
            'doctor' => $user?->doctorProfile,
            'roles' => $this->roles(),
            'users' => User::withTrashed()->latest()->get(),

        ]);
    }

    /**
     * Create user page.
     */
    public function create(): Response
    {
        $user = Auth::guard('web')->user();

        return Inertia::render('Portal/Users/Create', [
            'doctor' => $user?->doctorProfile,
            'roles' => $this->roles(),
        ]);
    }

    /**
     * Store new user.
     */
    public function store(Request $request)
    {
        $validated = $this->validateUser($request);

        User::create(
            $this->hashPassword($validated)
        );

        return redirect()
            ->route('portal.users.index')
            ->with('success', 'کاربر جدید با موفقیت ایجاد شد.');
    }

    /**
     * Edit user page.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('Portal/Users/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update user information.
     */
    public function update(Request $request, User $user)
    {
        $this->preventSelfModification($user);

        $validated = $this->validateUser($request, $user);

        $user->update(
            $this->hashPassword($validated)
        );

        return redirect()
            ->route('portal.users.index')
            ->with('success', 'اطلاعات کاربر با موفقیت ویرایش شد.');
    }

    /**
     * Change user status.
     */
    public function status(Request $request, User $user)
    {
        $this->preventSelfModification($user);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update($validated);

        return back()->with(
            'success',
            $user->is_active
            ? 'حساب کاربر با موفقیت فعال شد.'
            : 'حساب کاربر با موفقیت غیرفعال شد.'
        );
    }

    /**
     * Delete or restore user.
     */
    public function destroy(User $user)
    {
        $this->preventSelfModification($user);

        if ($user->trashed()) {
            $user->restore();

            return redirect()
                ->route('portal.users.index')
                ->with('success', 'کاربر با موفقیت بازیابی شد.');
        }

        $user->delete();

        return redirect()
            ->route('portal.users.index')
            ->with('success', 'کاربر با موفقیت حذف شد.');
    }
}