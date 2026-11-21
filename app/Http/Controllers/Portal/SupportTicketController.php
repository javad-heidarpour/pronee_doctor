<?php

declare(strict_types=1);

namespace App\\Http\\Controllers\\Portal;

use App\\Http\\Controllers\\Controller;
use App\\Models\\SupportTicket;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Auth;
use Illuminate\\Support\\Facades\\DB;
use Inertia\\Inertia;
use Inertia\\Response;

final class SupportTicketController extends Controller
{
    public function index(): Response
    {
        $this->authorizeSupportStaff();

        return Inertia::render('Portal/SupportTickets/Index', [
            'tickets' => SupportTicket::query()->with('responder:id,name,family')
                ->latest()->paginate(25)->withQueryString(),
        ]);
    }

    public function respond(Request $request, int $ticket): RedirectResponse
    {
        $this->authorizeSupportStaff();
        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved'],
            'staff_response' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::transaction(function () use ($ticket, $data): void {
            $item = SupportTicket::query()->lockForUpdate()->findOrFail($ticket);
            $item->update([
                'status' => $data['status'],
                'staff_response' => $data['staff_response'] ?? null,
                'responded_by' => Auth::guard('web')->id(),
                'responded_at' => now(),
            ]);
        });

        return back()->with('success', 'وضعیت درخواست پشتیبانی به‌روزرسانی شد.');
    }

    private function authorizeSupportStaff(): void
    {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);
    }
}
