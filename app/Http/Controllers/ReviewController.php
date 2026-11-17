<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Review\StoreReviewAction;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;

final class ReviewController extends Controller
{
    public function store(
        StoreReviewRequest $request,
        Doctor $doctor,
        StoreReviewAction $action
    ): RedirectResponse {
        return $action->handle($request, $doctor);
    }
}
