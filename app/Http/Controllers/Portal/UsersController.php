<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\User;
use App\Models\Doctor;
use App\Actions\Portal\Users\UpdateSecretaryAssignmentsAction;
use App\Actions\Portal\Users\CreateManagedUserAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    use HasActiveDoctor;

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

    /**
     * مدیر می‌تواند کاربران را مدیریت کند؛ پزشک فقط منشی مرتبط با
     * پزشک فعال خودش را. منشی اجازه مدیریت کاربران را ندارد.
     */
    private function authorizeUserManagement(?User $target = null): ?Doctor
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

    private function roles(): array
    {
        $authUser = Auth::guard('web')->user();

        $roles = [
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

        return $authUser?->role === 'admin'
            ? $roles
            : array_values(array_filter(
                $roles,
                fn ($role) => $role['value'] === 'secretary'
            ));
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $authUser = Auth::guard('web')->user();

        // ایجاد حساب پزشک باید از فرایند ثبت‌نام پزشک انجام شود تا
        // رکورد doctors و ارتباط آن با users نیز ساخته شود.
        // در ویرایش، نقش فعلی پزشک برای حفظ حساب‌های موجود مجاز می‌ماند.
        $allowedRoles = $authUser?->role === 'admin'
            ? ($user
                ? ['doctor', 'secretary', 'admin', 'user']
                : ['secretary', 'admin', 'user'])
            : ['secretary'];

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
            'role' => ['required', Rule::in($allowedRoles)],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function preventSelfModification(User $user): void
    {
        abort_if(
            (int) Auth::guard('web')->id() === (int) $user->id,
            403,
            'امکان تغییر حساب کاربری خودتان از این بخش وجود ندارد.'
        );
    }

    private function hashPassword(array $validated): array
    {
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        return $validated;
    }

    public function assignments(User $user): Response
    {
        $authUser = Auth::guard('web')->user();
        $activeDoctor = $this->authorizeUserManagement($user);

        abort_unless($user->role === 'secretary', 404);

        $doctorsQuery = Doctor::query()
            ->with(['user', 'address' => fn ($query) => $query->where('is_active', true)])
            ->where('doctors.is_active', true)
            ->whereHas('user', function ($query) {
                $query->where('role', 'doctor')
                    ->where('is_active', true);
            });

        if ($authUser->role === 'doctor') {
            $doctorsQuery->whereKey($activeDoctor->id);
        }

        $doctors = $doctorsQuery->orderBy('name')->get();

        $assigned = $user->doctors()->get()->keyBy('id');

        return Inertia::render('Portal/Users/Assignments', [
            'doctor' => $activeDoctor ?? $authUser->doctorProfile,
            'user' => $user->only(['id', 'name', 'family', 'phone', 'role']),
            'doctors' => $doctors->map(function ($doctor) use ($assigned) {
                $relation = $assigned->get($doctor->id);
                $permissions = $relation?->pivot?->permissions ?? [];
                $clinicIds = $relation?->pivot?->clinic_ids ?? [];

                if (is_string($permissions)) {
                    $permissions = json_decode($permissions, true) ?: [];
                }

                if (is_string($clinicIds)) {
                    $clinicIds = json_decode($clinicIds, true) ?: [];
                }

                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name ?: $doctor->user?->name,
                    'family' => $doctor->family ?: $doctor->user?->family,
                    'phone' => $doctor->phone ?: $doctor->user?->phone,
                    'specialty_title' => $doctor->specialty_title,
                    'assigned' => (bool) $relation,
                    'permissions' => $permissions,
                    'clinics' => $doctor->address->map(fn ($clinic) => [
                        'id' => $clinic->id,
                        'name' => $clinic->name,
                        'is_active' => (bool) $clinic->is_active,
                    ])->values(),
                    'clinic_ids' => array_map('intval', is_array($clinicIds) ? $clinicIds : []),
                ];
            })->values(),
            'permissions' => $this->availablePermissions(),
        ]);
    }

    public function updateAssignments(
        Request $request,
        User $user,
        UpdateSecretaryAssignmentsAction $action
    )
    {
        $authUser = Auth::guard('web')->user();
        $activeDoctor = $this->authorizeUserManagement($user);

        abort_unless($user->role === 'secretary', 404);

        $availablePermissions = array_keys($this->availablePermissions());

        $validated = $request->validate([
            'doctors' => ['present', 'array'],
            'doctors.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('doctors', 'id'),
            ],
            'doctors.*.permissions' => ['required', 'array'],
            'doctors.*.clinic_ids' => ['required', 'array', 'min:1'],
            'doctors.*.clinic_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('addresses', 'id'),
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

        if ($authUser->role === 'doctor') {
            abort_unless(
                count(array_diff($doctorIds, [$activeDoctor->id])) === 0,
                403,
                'شما فقط مجاز به اتصال منشی به پزشک فعال خودتان هستید.'
            );
        }

        $doctors = Doctor::query()
            ->with('user')
            ->whereIn('id', $doctorIds)
            ->get()
            ->keyBy('id');

        foreach ($doctorIds as $doctorId) {
            $doctor = $doctors->get($doctorId);

            abort_unless(
                $doctor &&
                $doctor->user &&
                $doctor->user->role === 'doctor' &&
                $doctor->user->is_active &&
                $doctor->is_active,
                422,
                'یکی از پزشکان انتخاب‌شده معتبر یا فعال نیست.'
            );
        }

        foreach ($validated['doctors'] as $item) {
            $validClinicIds = $doctors->get((int) $item['id'])
                ->address()
                ->where('addresses.is_active', true)
                ->whereIn('addresses.id', $item['clinic_ids'])
                ->pluck('addresses.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            abort_unless(
                count($validClinicIds) === count($item['clinic_ids']),
                422,
                'یکی از مطب‌های انتخاب‌شده متعلق به پزشک مربوطه نیست.'
            );

            if (count($validClinicIds) === 0) {
                abort(422, 'برای هر پزشک متصل، حداقل یک مطب انتخاب کنید.');
            }
        }

        $syncData = [];

        foreach ($validated['doctors'] as $item) {
            $syncData[$item['id']] = [
                'permissions' => json_encode(
                    $item['permissions'],
                    JSON_UNESCAPED_UNICODE
                ),
                'clinic_ids' => json_encode(
                    array_values(array_map('intval', $item['clinic_ids'])),
                    JSON_UNESCAPED_UNICODE
                ),
            ];
        }

        $action->handle(
            $user,
            $syncData,
            $authUser->role,
            $activeDoctor,
            $doctorIds
        );

        return redirect()
            ->route('portal.users.assignments', $user->id)
            ->with('success', 'ارتباط پزشکان و سطح دسترسی منشی با موفقیت ذخیره شد.');
    }

    public function index(): Response
    {
        $authUser = Auth::guard('web')->user();
        $activeDoctor = $this->authorizeUserManagement();

        $usersQuery = User::withTrashed()->latest();

        if ($authUser->role === 'doctor') {
            $usersQuery->where('role', 'secretary')
                ->whereHas('doctors', function ($query) use ($activeDoctor) {
                    $query->where('doctors.id', $activeDoctor->id);
                });
        }

        return Inertia::render('Portal/Users/Index', [
            'doctor' => $activeDoctor ?? $authUser->doctorProfile,
            'roles' => $this->roles(),
            'users' => $usersQuery->get(),
        ]);
    }

    public function create(): Response
    {
        $authUser = Auth::guard('web')->user();
        $activeDoctor = $this->authorizeUserManagement();

        $roles = array_values(array_filter(
            $this->roles(),
            fn ($role) => $role['value'] !== 'doctor'
        ));

        return Inertia::render('Portal/Users/Create', [
            'doctor' => $activeDoctor ?? $authUser->doctorProfile,
            'roles' => $roles,
        ]);
    }

    public function store(
        Request $request,
        CreateManagedUserAction $action
    ) {
        $activeDoctor = $this->authorizeUserManagement();
        $validated = $this->validateUser($request);

        $secretary = $action->handle($validated, $activeDoctor);

        $redirect = $secretary->role === 'secretary'
            ? redirect()->route('portal.users.assignments', $secretary->id)
            : redirect()->route('portal.users.index');

        return $redirect->with(
            'success',
            $secretary->role === 'secretary'
                ? 'منشی ایجاد شد؛ اکنون سطح دسترسی او را تعیین کنید.'
                : 'کاربر جدید با موفقیت ایجاد شد.'
        );
    }

    public function edit(User $user): Response
    {
        $this->authorizeUserManagement($user);

        return Inertia::render('Portal/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeUserManagement($user);
        $this->preventSelfModification($user);

        $validated = $this->validateUser($request, $user);
        $user->update($this->hashPassword($validated));

        return redirect()
            ->route('portal.users.index')
            ->with('success', 'اطلاعات کاربر با موفقیت ویرایش شد.');
    }

    public function status(Request $request, User $user)
    {
        $this->authorizeUserManagement($user);
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

    public function destroy(User $user)
    {
        $this->authorizeUserManagement($user);
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