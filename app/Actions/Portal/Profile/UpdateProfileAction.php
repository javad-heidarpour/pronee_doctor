<?php

declare(strict_types=1);

namespace App\Actions\Portal\Profile;

use App\DTOs\Portal\Profile\UpdateProfileDto;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class UpdateProfileAction
{
    public function __construct(
        private readonly ProfileService $service,
    ) {
    }

    public function handle(UpdateProfileRequest $request): RedirectResponse
    {
        $dto = UpdateProfileDto::fromRequest($request);

        return $this->service->update($dto);
    }
}