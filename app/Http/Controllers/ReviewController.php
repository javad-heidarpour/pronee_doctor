<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Review\StoreReviewAction;
use App\DTOs\Review\StoreReviewData;
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
        $data = StoreReviewData::fromValidated(
            $request->validated(),
            $request->user('web')?->getAuthIdentifier()
        );

        $action->handle($doctor, $data);

        return back()->with(
            'success',
            'نظر شما با موفقیت ثبت شد و پس از تأیید پزشک نمایش داده خواهد شد.'
        );
    }
}
