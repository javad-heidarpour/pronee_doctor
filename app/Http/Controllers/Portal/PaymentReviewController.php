<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Payments\ListPaymentsRequiringReviewAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReviewController extends Controller
{
    /**
     * Gateway reconciliation queue is restricted to system administrators.
     */
    public function index(
        Request $request,
        ListPaymentsRequiringReviewAction $action
    ): Response {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'gateway' => ['nullable', 'in:sep'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return Inertia::render('Portal/Payments/ReviewQueue', [
            'payments' => $action->handle($filters),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'gateway' => $filters['gateway'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
        ]);
    }
}
