<?php

declare(strict_types=1);

namespace App\\Http\\Controllers;

use App\\Models\\SupportTicket;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Str;
use Inertia\\Inertia;
use Inertia\\Response;

final class SupportTicketController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Support/Index');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'category' => ['required', 'in:booking,account,technical,other'],
            'subject' => ['required', 'string', 'min:4', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        $user = $request->user('web');
        $ticket = SupportTicket::query()->create([
            ...$data,
            'user_id' => $user?->id,
            'reference' => 'PNX-' . Str::upper(Str::random(8)),
            'status' => 'open',
        ]);

        return back()->with('ticket_reference', $ticket->reference)
            ->with('success', 'درخواست شما ثبت شد. کد پیگیری را نگه دارید.');
    }
}
