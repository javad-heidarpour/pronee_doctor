<?php

declare(strict_types=1);

namespace App\Actions\Portal\Profile;

use App\DTOs\Profile\DeleteProfileDto;
use App\Http\Requests\Profile\DeleteProfileRequest;
use App\Services\ProfileService;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DeleteProfileAction
{
    public function __construct(
        private readonly ProfileService $service,
    ) {
    }

    public function handle(DeleteProfileRequest $request): RedirectResponse
    {
        $dto = DeleteProfileDto::fromRequest($request);

        return $this->service->destroy($dto);
    }
}