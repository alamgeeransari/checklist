<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    private array $defaultPreferences = [
        'mail_enabled' => true,
        'push_enabled' => true,
        'task_created_enabled' => true,
        'step_assigned_enabled' => true,
        'step_completed_enabled' => true,
        'task_restarted_enabled' => true,
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->where('id', $notification)->firstOrFail();
        $record->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $user->notificationPreference;

        if (! $preferences) {
            $preferences = UserNotificationPreference::query()->create([
                'user_id' => $user->id,
                ...$this->defaultPreferences,
            ]);
        }

        return response()->json($preferences);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'mail_enabled' => ['sometimes', 'boolean'],
            'push_enabled' => ['sometimes', 'boolean'],
            'task_created_enabled' => ['sometimes', 'boolean'],
            'step_assigned_enabled' => ['sometimes', 'boolean'],
            'step_completed_enabled' => ['sometimes', 'boolean'],
            'task_restarted_enabled' => ['sometimes', 'boolean'],
        ]);

        $preferences = $user->notificationPreference;
        if (! $preferences) {
            $preferences = UserNotificationPreference::query()->create([
                'user_id' => $user->id,
                ...$this->defaultPreferences,
            ]);
        }

        $preferences->update($data);

        return response()->json($preferences->refresh());
    }
}
