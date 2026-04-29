<?php

namespace App\Notifications;

use App\Models\CompanySetting;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Task $task)
    {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $companyId = $notifiable->company_id ?? $this->task->company_id;
        $mailEnabled = true;
        if ($companyId) {
            $mailEnabled = (bool) CompanySetting::query()
                ->where('company_id', $companyId)
                ->value('mail_notifications_enabled');
        }

        $pref = $notifiable->notificationPreference;
        $userWantsMail = $pref?->mail_enabled ?? true;
        $userEventEnabled = $pref?->task_created_enabled ?? true;

        if ($mailEnabled && $userWantsMail && $userEventEnabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New Task Created: ' . $this->task->title)
            ->line('A new release checklist task has been created.')
            ->line('Project: ' . $this->task->project?->name)
            ->line('Task: ' . $this->task->title);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'status' => $this->task->status->value,
        ];
    }
}
