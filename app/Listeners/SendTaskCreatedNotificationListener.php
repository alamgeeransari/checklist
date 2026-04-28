<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Notifications\TaskCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskCreatedNotificationListener implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(TaskCreated $event): void
    {
        if ($event->task->creator !== null) {
            $event->task->creator->notify(new TaskCreatedNotification($event->task));
        }
    }
}
