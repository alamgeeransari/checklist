<?php

namespace App\Providers;

use App\Events\StepAssigned;
use App\Events\StepCompleted;
use App\Events\TaskCreated;
use App\Events\TaskRestarted;
use App\Listeners\SendStepAssignedNotificationListener;
use App\Listeners\SendStepCompletedNotificationListener;
use App\Listeners\SendTaskCreatedNotificationListener;
use App\Listeners\SendTaskRestartedNotificationListener;
use App\Listeners\WriteAuditLogListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TaskCreated::class => [
            SendTaskCreatedNotificationListener::class,
            WriteAuditLogListener::class,
        ],
        StepAssigned::class => [
            SendStepAssignedNotificationListener::class,
            WriteAuditLogListener::class,
        ],
        StepCompleted::class => [
            SendStepCompletedNotificationListener::class,
            WriteAuditLogListener::class,
        ],
        TaskRestarted::class => [
            SendTaskRestartedNotificationListener::class,
            WriteAuditLogListener::class,
        ],
    ];
}
