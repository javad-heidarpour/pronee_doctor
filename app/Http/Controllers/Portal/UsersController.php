<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SaveManagedUserRequest;
use App\Http\Requests\Portal\UpdateManagedUserStatusRequest;
use App\Http\Requests\Portal\UpdateSecretaryAssignmentsRequest;
use App\Http\Controllers\Portal\Concerns\AuthorizesManagedUsers;
use App\Models\User;
use App\Actions\Portal\Users\UpdateSecretaryAssignmentsAction;
use App\Actions\Portal\Users\BuildSecretaryAssignmentsDataAction;
use App\Actions\Portal\Users\BuildManagedUserAssignmentsPageAction;
use App\Actions\Portal\Users\BuildManagedUsersIndexPageAction;
use App\Actions\Portal\Users\BuildManagedUserCreatePageAction;
use App\Actions\Portal\Users\CreateManagedUserAction;
use App\Actions\Portal\Users\UpdateManagedUserAction;
use App\Actions\Portal\Users\ChangeManagedUserStatusAction;
use App\Actions\Portal\Users\RestoreOrDeleteManagedUserAction;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    use AuthorizesManagedUsers;

    public function assignments(
        User $user,
        BuildManagedUserAssignmentsPageAction $pageAction
    ): Response {
        $authUser = Auth::guard('web')->user();
        $activeDoctor = $this->authorizeUserManagement($user);

        abort_unless($user->role === 'secretary', 404);

        return Inertia::render(
            'Portal/Users/Assignments',
            $pageAction->handle($user, $authUser, $activeDoctor)
        );
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

    public function index(BuildManagedUsersIndexPageAction $action): Response
    {
        $authUser = Auth::guard('web')->user();
        $activeDoctor = $this->authorizeUserManagement();

        return Inertia::render(
            'Portal/Users/Index',
            $action->handle($authUser, $activeDoctor)
        );
    }

    public function create(BuildManagedUserCreatePageAction $action): Response
    {
        $authUser = Auth::guard('web')->user();
        $activeDoctor = $this->authorizeUserManagement();

        return Inertia::render(
            'Portal/Users/Create',
            $action->handle($authUser, $activeDoctor)
        );
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