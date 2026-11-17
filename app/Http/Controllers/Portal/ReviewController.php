<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Reviews\DeletePortalReviewAction;
use App\Actions\Portal\Reviews\ListPortalReviewsAction;
use App\Actions\Portal\Reviews\TogglePortalReviewApprovalAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\ListPortalReviewsRequest;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    use HasActiveDoctor;

    public function index(
        ListPortalReviewsRequest $request,
        ListPortalReviewsAction $action
    ): Response {
        $doctor = $this->currentDoctor();
        $filter = $request->validated('filter', 'all');

        return Inertia::render('Portal/Reviews', [
            'doctor' => $doctor,
            'reviews' => $action->handle($doctor, $filter),
            'filter' => $filter,
        ]);
    }

    public function toggleApproval(
        Review $review,
        TogglePortalReviewApprovalAction $action
    ): RedirectResponse {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $action->handle($doctor, $review);

        return back()->with('success', 'وضعیت نظر تغییر کرد.');
    }

    public function destroy(
        Review $review,
        DeletePortalReviewAction $action
    ): RedirectResponse {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $action->handle($doctor, $review);

        return back()->with('success', 'نظر حذف شد.');
    }
}
