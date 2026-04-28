<?php

namespace App\Listeners;

use App\Events\TaskRestarted;
use App\Models\User;
use App\Notifications\TaskRestartedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendTaskRestartedNotificationListener implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(TaskRestarted $event): void
    {
        $task = $event->task;

        $users = User::query()
            ->where('company_id', $task->company_id)
            ->where('is_active', true)
            ->get();

        Notification::send($users, new TaskRestartedNotification($task, $event->reason));
    }
}
