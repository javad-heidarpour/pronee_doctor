<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Reviews\DeletePortalReviewAction;
use App\Actions\Portal\Reviews\ListPortalReviewsAction;
use App\Actions\Portal\Reviews\TogglePortalReviewApprovalAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    use HasActiveDoctor;

    public function index(
        Request $request,
        ListPortalReviewsAction $action
    ) {
        $doctor = $this->currentDoctor();
        $filter = (string) $request->input('filter', 'all');

        return Inertia::render('Portal/Reviews', [
            'doctor' => $doctor,
            'reviews' => $action->handle($doctor, $filter),
            'filter' => $filter,
        ]);
    }

    public function toggleApproval(
        Review $review,
        TogglePortalReviewApprovalAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $action->handle($doctor, $review);

        return back()->with('success', 'وضعیت نظر تغییر کرد.');
    }

    public function destroy(
        Review $review,
        DeletePortalReviewAction $action
    ) {
        $action->handle($this->currentDoctor(), $review);

        return back()->with('success', 'نظر حذف شد.');
    }
}
