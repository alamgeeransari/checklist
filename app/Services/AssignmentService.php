<?php

namespace App\Services;

use App\Enums\TaskStepStatus;
use App\Events\StepAssigned;
use App\Models\TaskStep;
use App\Models\User;
use App\Repositories\Contracts\TaskStepRepositoryInterface;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(private readonly TaskStepRepositoryInterface $taskStepRepository)
    {
    }

    public function assignStep(
        TaskStep $taskStep,
        User $assignee,
        User $assignedBy,
        bool $override = false,
        ?string $reason = null,
        bool $isReassignment = false
    ): TaskStep {
        if (!$assignee->is_active) {
            throw ValidationException::withMessages([
                'assignee' => 'Cannot assign to an inactive user.',
            ]);
        }

        $isTeamMember = $taskStep->team
            ->members()
            ->where('users.id', $assignee->id)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$isTeamMember && !$override) {
            throw ValidationException::withMessages([
                'assignee' => 'Selected user is not an active member of this team.',
            ]);
        }

        $currentStatus = $taskStep->status instanceof TaskStepStatus
            ? $taskStep->status->value
            : (string) $taskStep->status;

        if (! in_array($currentStatus, [
            TaskStepStatus::Pending->value,
            TaskStepStatus::Assigned->value,
            TaskStepStatus::InProgress->value,
        ], true)) {
            throw ValidationException::withMessages([
                'task_step' => 'Step cannot be assigned in its current status.',
            ]);
        }

        $fromUserId = $taskStep->assigned_to;

        $updated = $this->taskStepRepository->updateAssignment(
            $taskStep,
            $assignee->id,
            $assignedBy->id,
            $override
        );

        $this->taskStepRepository->recordAssignmentHistory(
            $updated,
            $fromUserId,
            $assignee->id,
            $assignedBy->id,
            $reason,
            $isReassignment
        );

        $updated = $updated->fresh(['team', 'assignee']);
        event(new StepAssigned($updated, $isReassignment));

        return $updated;
    }

    public function reassignStep(
        TaskStep $taskStep,
        User $assignee,
        User $assignedBy,
        ?string $reason = null,
        bool $override = false
    ): TaskStep {
        return $this->assignStep($taskStep, $assignee, $assignedBy, $override, $reason, true);
    }
}
