<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResetUserPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreNewPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function create(StoreNewPasswordRequest $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->query('email'),
            'token' => $request->route('token'),
        ]);
    }

    public function store(
        StoreNewPasswordRequest $request,
        ResetUserPasswordAction $action
    ): RedirectResponse {
        $status = $action->handle($request->validated());

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
