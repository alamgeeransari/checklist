<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskRestartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly string $reason
    ) {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $companyMailEnabled = $notifiable->company?->settings?->mail_notifications_enabled ?? true;
        $userMailEnabled = $notifiable->notificationPreference?->mail_enabled ?? true;
        $eventEnabled = $notifiable->notificationPreference?->task_restarted_enabled ?? true;

        if ($companyMailEnabled && $userMailEnabled && $eventEnabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Task restarted: {$this->task->title}")
            ->line("Task #{$this->task->id} has been restarted from the first workflow step.")
            ->line("Reason: {$this->reason}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'reason' => $this->reason,
            'event' => 'task_restarted',
        ];
    }
}
