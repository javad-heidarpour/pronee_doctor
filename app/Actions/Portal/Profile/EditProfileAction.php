<?php

declare(strict_types=1);

namespace App\Actions\Portal\Profile;

use App\Services\ProfileService;
use Illuminate\Http\Request;
use Inertia\Response;

final class EditProfileAction
{
    public function __construct(
        private readonly ProfileService $service,
    ) {
    }

    public function handle(Request $request): Response
    {
        return $this->service->edit($request);
    }
}