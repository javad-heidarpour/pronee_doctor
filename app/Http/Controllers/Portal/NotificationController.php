<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationController extends Controller
{
    public function index(): Response
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20)
            ->through(function (DatabaseNotification $notification): array {
                $data = is_array($notification->data) ? $notification->data : [];

                return [
                    'id' => $notification->id,
                    'type' => class_basename($notification->type),
                    'title' => (string) ($data['title'] ?? 'اعلان جدید'),
                    'message' => (string) ($data['message'] ?? $data['body'] ?? 'پیام جدیدی برای شما ثبت شده است.'),
                    'url' => $this->safeInternalUrl($data['url'] ?? null),
                    'created_at' => $notification->created_at?->toIso8601String(),
                    'read_at' => $notification->read_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Portal/Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(string $notification): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);

        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return back()->with('success', 'اعلان به‌عنوان خوانده‌شده ثبت شد.');
    }

    public function markAllRead(): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'همه اعلان‌ها خوانده‌شده شدند.');
    }

    private function safeInternalUrl(mixed $url): ?string
    {
        if (!is_string($url) || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        return $url;
    }
}
