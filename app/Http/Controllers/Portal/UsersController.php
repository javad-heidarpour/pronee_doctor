<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SaveManagedUserRequest;
use App\Http\Requests\Portal\UpdateManagedUserStatusRequest;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\User;
use App\Models\Doctor;
use App\Actions\Portal\Users\UpdateSecretaryAssignmentsAction;
use App\Actions\Portal\Users\ListManagedUserPermissionsAction;
use App\Actions\Portal\Users\ListManagedUserRolesAction;
use App\Actions\Portal\Users\CreateManagedUserAction;
use App\Actions\Portal\Users\UpdateManagedUserAction;
use App\Actions\Portal\Users\ChangeManagedUserStatusAction;
use App\Actions\Portal\Users\RestoreOrDeleteManagedUserAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    use HasActiveDoctor;

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

    private function preventSelfModification(User $user): void
    {
        abort_if(
            (int) Auth::guard('web')->id() === (int) $user->id,
            403,
            'امکان تغییر حساب کاربری خودتان از این بخش وجود ندارد.'
        );
    }

    public function assignments(User $user, ListManagedUserPermissionsAction $permissionsAction): Response
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
            'permissions' => $permissionsAction->handle(),
        ]);
    }

    public function updateAssignments(
        UpdateSecretaryAssignmentsRequest $request,
        User $user,
        UpdateSecretaryAssignmentsAction $action,
        BuildSecretaryAssignmentsDataAction $dataAction
    ) {
        $authUser = Auth::guard('web')->user();
        $activeDoctor = $this->authorizeUserManagement($user);

        abort_unless($user->role === 'secretary', 404);

        $validated = $request->validated();
        $doctorIds = collect($validated['doctors'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $syncData = $dataAction->handle(
            $validated,
            $authUser->role,
            $activeDoctor
        );

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

    public function index(ListManagedUserRolesAction $rolesAction): Response
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
            'roles' => $rolesAction->handle($authUser),
            'users' => $usersQuery->get(),
        ]);
    }

    public function create(ListManagedUserRolesAction $rolesAction): Response
    {
        $authUser = Auth::guard('web')->user();
        $activeDoctor = $this->authorizeUserManagement();

        $roles = array_values(array_filter(
            $rolesAction->handle($authUser),
            fn ($role) => $role['value'] !== 'doctor'
        ));

        return Inertia::render('Portal/Users/Create', [
            'doctor' => $activeDoctor ?? $authUser->doctorProfile,
            'roles' => $roles,
        ]);
    }

    public function store(
        SaveManagedUserRequest $request,
        CreateManagedUserAction $action
    ) {
        $activeDoctor = $this->authorizeUserManagement();
        $validated = $request->validated();

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

    public function update(
        SaveManagedUserRequest $request,
        User $user,
        UpdateManagedUserAction $action
    ) {
        $this->authorizeUserManagement($user);
        $this->preventSelfModification($user);

        $validated = $request->validated();
        $action->handle($user, $validated);

        return redirect()
            ->route('portal.users.index')
            ->with('success', 'اطلاعات کاربر با موفقیت ویرایش شد.');
    }

    public function status(
        UpdateManagedUserStatusRequest $request,
        User $user,
        ChangeManagedUserStatusAction $action
    ) {
        $this->authorizeUserManagement($user);
        $this->preventSelfModification($user);

        $validated = $request->validated();

        $updatedUser = $action->handle($user, (bool) $validated['is_active']);

        return back()->with(
            'success',
            $updatedUser->is_active
                ? 'حساب کاربر با موفقیت فعال شد.'
                : 'حساب کاربر با موفقیت غیرفعال شد.'
        );
    }

    public function destroy(
        User $user,
        RestoreOrDeleteManagedUserAction $action
    ) {
        $this->authorizeUserManagement($user);
        $this->preventSelfModification($user);

        $restored = $action->handle($user);

        return redirect()
            ->route('portal.users.index')
            ->with(
                'success',
                $restored
                    ? 'کاربر با موفقیت بازیابی شد.'
                    : 'کاربر با موفقیت حذف شد.'
            );
    }
}