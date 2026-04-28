<?php

namespace App\Listeners;

use App\Events\StepAssigned;
use App\Notifications\StepAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendStepAssignedNotificationListener implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(StepAssigned $event): void
    {
        if ($event->step->assignee !== null) {
            $event->step->assignee->notify(new StepAssignedNotification($event->step, $event->isReassignment));
        }
    }
}
