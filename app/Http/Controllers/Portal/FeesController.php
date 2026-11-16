<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Fees\ListFeesAction;
use App\Actions\Portal\Fees\UpdateFeeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateFeeRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FeesController extends Controller
{
    public function index(ListFeesAction $action): Response
    {
        return Inertia::render('Portal/Settings', $action->handle());
    }

    public function updateFee(UpdateFeeRequest $request, UpdateFeeAction $action): RedirectResponse
    {
        $changed = $action->handle((int) $request->validated()['fee_rial']);

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
