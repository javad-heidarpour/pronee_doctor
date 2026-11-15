<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FeesController extends Controller
{
    public function index(): Response
    {
        $fees = Fee::with('creator:id,name,caption,family')
            ->orderByDesc('id')
            ->get();

        $activeFee = $fees->first(
            fn(Fee $fee) => $fee->is_active
        );

$user = Auth::guard('web')->user();
$doctor = $user->doctorProfile;
        return Inertia::render('Portal/Settings', [
            'activeFee' => $activeFee,
            'feeHistory' => $fees,
            'doctor' => $doctor,
        ]);
    }

    public function updateFee(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fee_rial' => [
                'required',
                'integer',
                'min:1',
                'max:999999999999',
            ],
        ], [
            'fee_rial.required' => 'مبلغ تعرفه را وارد کنید.',
            'fee_rial.integer' => 'مبلغ تعرفه باید عدد صحیح باشد.',
            'fee_rial.min' => 'مبلغ تعرفه باید بیشتر از صفر باشد.',
            'fee_rial.max' => 'مبلغ واردشده بیش از حد مجاز است.',
        ]);

        $newFee = (int) $validated['fee_rial'];

        $creatorId = Auth::guard('doctor')->id()
            ?? Auth::id();

        $changed = DB::transaction(function () use ($newFee, $creatorId) {
            $activeFee = Fee::where('is_active', true)
                ->lockForUpdate()
                ->first();

            // مبلغ تغییر نکرده است
            if ($activeFee && (int) $activeFee->fee_rial === $newFee) {
                return false;
            }

            Fee::where('is_active', true)
                ->get()
                ->each(function (Fee $fee) {
                    $fee->is_active = false;
                    $fee->deactivated_at = now();
                    $fee->save();
                });

            Fee::create([
                'title' => 'تعرفه ویزیت',
                'fee_rial' => $newFee,
                'is_active' => true,
                'created_by' => $creatorId,
            ]);

            Appointment::updateUnpaidAppointmentsFee($newFee);

            return true;
        });

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