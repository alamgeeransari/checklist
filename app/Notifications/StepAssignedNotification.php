<?php

namespace App\Notifications;

use App\Models\CompanySetting;
use App\Models\TaskStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StepAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TaskStep $step,
        private readonly bool $isReassignment = false
    )
    {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $companyId = $notifiable->company_id ?? $this->step->cycle?->task?->company_id;
        $mailEnabled = true;
        if ($companyId) {
            $mailEnabled = (bool) CompanySetting::query()
                ->where('company_id', $companyId)
                ->value('mail_notifications_enabled');
        }
        $pref = $notifiable->notificationPreference;
        $userMailEnabled = $pref?->mail_enabled ?? true;
        $eventEnabled = $pref?->step_assigned_enabled ?? true;

        if ($mailEnabled && $userMailEnabled && $eventEnabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $task = $this->step->cycle?->task;
        return (new MailMessage())
            ->subject($this->isReassignment ? 'Checklist Step Reassigned' : 'New Checklist Step Assigned')
            ->line($this->isReassignment ? 'A checklist step was reassigned to you.' : 'You have been assigned a checklist step.')
            ->line('Task: ' . ($task?->title ?? 'N/A'))
            ->line('Step Order: ' . $this->step->step_order);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->step->cycle?->task_id,
            'task_step_id' => $this->step->id,
            'step_order' => $this->step->step_order,
            'is_reassignment' => $this->isReassignment,
        ];
    }
}
