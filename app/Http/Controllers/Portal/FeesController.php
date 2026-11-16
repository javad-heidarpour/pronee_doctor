<?php

namespace App\\Http\\Controllers\\Portal;

use App\\Actions\\Portal\\Fees\\ListFeesAction;
use App\\Actions\\Portal\\Fees\\UpdateFeeAction;
use App\\Http\\Controllers\\Controller;
use App\\Http\\Controllers\\Portal\\Concerns\\HasActiveDoctor;
use App\\Http\\Requests\\Portal\\UpdateFeeRequest;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Support\\Facades\\Auth;
use Inertia\\Inertia;
use Inertia\\Response;

class FeesController extends Controller
{
    use HasActiveDoctor;

    public function index(ListFeesAction $action): Response
    {
        $doctor = $this->currentDoctor();

        return Inertia::render('Portal/Settings', $action->handle($doctor));
    }

    public function updateFee(
        UpdateFeeRequest $request,
        UpdateFeeAction $action
    ): RedirectResponse {
        // حتی عملیات تغییر تعرفه نیز باید در زمینه پزشک فعال انجام شود.
        $this->currentDoctor();

        $user = Auth::guard('web')->user();

        abort_unless($user, 401);

        $changed = $action->handle(
            (int) $request->validated()['fee_rial'],
            (int) $user->id
        );

        if (!$changed) {
            return redirect()
                ->route('portal.Fees.index')
                ->with('info', 'مبلغ تعرفه تغییری نکرده است؛ عملیاتی انجام نشد.');
        }

        return redirect()
            ->route('portal.Fees.index')
            ->with('success', 'تعرفه جدید با موفقیت ثبت شد.');
    }
}