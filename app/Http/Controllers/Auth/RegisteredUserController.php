<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterPublicUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterPublicUserRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(
        RegisterPublicUserRequest $request,
        RegisterPublicUserAction $action
    ): RedirectResponse {
        $action->handle($request->validated());

        return redirect()->route('dashboard', absolute: false);
    }
}
