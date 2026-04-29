<?php

namespace App\Notifications;

use App\Models\CompanySetting;
use App\Models\TaskStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StepCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TaskStep $step,
    ) {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $companyId = $notifiable->company_id ?? null;
        $mailEnabledAtCompany = true;
        if ($companyId !== null) {
            $mailEnabledAtCompany = (bool) CompanySetting::query()
                ->where('company_id', $companyId)
                ->value('mail_notifications_enabled');
        }

        $mailEnabledForUser = $notifiable->notificationPreference?->mail_enabled ?? true;
        $eventEnabledForUser = $notifiable->notificationPreference?->step_completed_enabled ?? true;

        if ($mailEnabledAtCompany && $mailEnabledForUser && $eventEnabledForUser) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Checklist Step Completed')
            ->line("Step '{$this->step->workflowStep->name}' for task '{$this->step->cycle->task->title}' has been completed.")
            ->line('The workflow has moved to the next step if available.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->step->cycle->task_id,
            'task_step_id' => $this->step->id,
            'workflow_step' => $this->step->workflowStep->name,
            'status' => 'completed',
        ];
    }
}
