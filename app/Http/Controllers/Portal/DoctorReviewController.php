<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\DoctorReviews\ListDoctorReviewsAction;
use App\Actions\Portal\DoctorReviews\ReplyDoctorReviewAction;
use App\Actions\Portal\DoctorReviews\ToggleDoctorReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\ListDoctorReviewsRequest;
use App\Http\Requests\Portal\ReplyDoctorReviewRequest;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DoctorReviewController extends Controller
{
    use HasActiveDoctor;

    public function index(
        ListDoctorReviewsRequest $request,
        ListDoctorReviewsAction $action
    ): Response {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $action->handle($request->validated(), $doctor);

        return Inertia::render('Portal/DoctorReviews', [
            'doctor' => $doctor,
            ...$data,
        ]);
    }

    public function toggle(
        Review $review,
        ToggleDoctorReviewAction $action
    ): RedirectResponse {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $approved = $action->handle($doctor, $review);

        return back()->with(
            'success',
            $approved ? 'نظر تأیید شد.' : 'نظر از حالت تأیید خارج شد.'
        );
    }

    public function reply(
        ReplyDoctorReviewRequest $request,
        Review $review,
        ReplyDoctorReviewAction $action
    ): RedirectResponse {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();

        $action->handle(
            $doctor,
            $review,
            $request->validated('doctor_reply')
        );

        return back()->with('success', 'پاسخ پزشک ثبت شد.');
    }
}
