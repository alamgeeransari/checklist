<?php

namespace App\Listeners;

use App\Events\StepCompleted;
use App\Notifications\StepCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendStepCompletedNotificationListener implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(StepCompleted $event): void
    {
        $task = $event->step->cycle->task;
        $notifiables = array_filter([$task->creator]);

        Notification::send($notifiables, new StepCompletedNotification($event->step));
    }
}
