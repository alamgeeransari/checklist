<?php

namespace App\Listeners;

use App\Events\StepAssigned;
use App\Events\StepCompleted;
use App\Events\TaskCreated;
use App\Events\TaskRestarted;
use App\Models\AuditLog;

class WriteAuditLogListener
{
    public function handle(object $event): void
    {
        if ($event instanceof TaskCreated) {
            AuditLog::query()->create([
                'company_id' => $event->task->company_id,
                'project_id' => $event->task->project_id,
                'user_id' => $event->task->created_by,
                'action' => 'workflow.task_created',
                'entity_type' => $event->task::class,
                'entity_id' => $event->task->id,
                'before_data' => null,
                'after_data' => ['status' => (string) $event->task->status->value],
            ]);

            return;
        }

        if ($event instanceof StepAssigned) {
            $task = $event->step->cycle->task;

            AuditLog::query()->create([
                'company_id' => $task->company_id,
                'project_id' => $task->project_id,
                'user_id' => $event->step->assigned_by,
                'action' => 'workflow.step_assigned',
                'entity_type' => $event->step::class,
                'entity_id' => $event->step->id,
                'before_data' => null,
                'after_data' => [
                    'status' => (string) $event->step->status->value,
                    'assignee_id' => $event->step->assigned_to,
                    'is_reassignment' => $event->isReassignment,
                ],
            ]);

            return;
        }

        if ($event instanceof StepCompleted) {
            $task = $event->step->cycle->task;

            AuditLog::query()->create([
                'company_id' => $task->company_id,
                'project_id' => $task->project_id,
                'user_id' => $event->actor->id,
                'action' => 'workflow.step_completed',
                'entity_type' => $event->step::class,
                'entity_id' => $event->step->id,
                'before_data' => null,
                'after_data' => ['status' => (string) $event->step->status->value],
            ]);

            return;
        }

        if ($event instanceof TaskRestarted) {
            AuditLog::query()->create([
                'company_id' => $event->task->company_id,
                'project_id' => $event->task->project_id,
                'user_id' => $event->actor->id,
                'action' => 'workflow.task_restarted',
                'entity_type' => $event->task::class,
                'entity_id' => $event->task->id,
                'before_data' => null,
                'after_data' => [
                    'status' => (string) $event->task->status->value,
                    'reason' => $event->reason,
                    'cycle_no' => $event->task->current_cycle_no,
                ],
            ]);
        }
    }
}
