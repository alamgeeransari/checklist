<?php

namespace App\Notifications;

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
        return ['mail', 'database'];
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
