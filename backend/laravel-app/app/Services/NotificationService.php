<?php
namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification for a user (player).
     */
    public function create($userId, $type, $message, $title = null, $options = [])
    {
        try {
            $notification = new Notification();
            $notification->user_id = $userId;
            $notification->type = $type;
            $notification->title = $title;
            $notification->message = $message;
            $notification->is_read = false;
            $notification->priority = $options['priority'] ?? 'normal';
            $notification->link = $options['link'] ?? null;
            $notification->icon = $options['icon'] ?? null;
            $notification->related_id = $options['related_id'] ?? null;
            $notification->related_type = $options['related_type'] ?? null;
            $notification->expires_at = $options['expires_at'] ?? null;
            $notification->save();
            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead($userId)
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead($notificationId, $userId)
    {
        $notification = Notification::where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first();
        if ($notification && !$notification->is_read) {
            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();
        }
        return $notification;
    }
}
