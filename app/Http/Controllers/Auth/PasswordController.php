<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\UpdateAuthenticatedUserPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateAuthenticatedPasswordRequest;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    /**
     * Update the authenticated user's password.
     */
    public function update(
        UpdateAuthenticatedPasswordRequest $request,
        UpdateAuthenticatedUserPasswordAction $action
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user instanceof \App\Models\User, 401);

        $action->handle($user, $request->string('password')->toString());

        return back();
    }
}
