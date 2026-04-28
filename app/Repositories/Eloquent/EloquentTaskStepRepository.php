<?php

namespace App\Repositories\Eloquent;

use App\Models\TaskStep;
use App\Enums\TaskStepStatus;
use App\Repositories\Contracts\TaskStepRepositoryInterface;

class EloquentTaskStepRepository implements TaskStepRepositoryInterface
{
    public function save(TaskStep $taskStep): TaskStep
    {
        $taskStep->save();

        return $taskStep;
    }

    public function findByIdOrFail(int $taskStepId): TaskStep
    {
        return TaskStep::query()
            ->with(['cycle.task', 'workflowStep.questionSets.questions', 'assignee', 'team.members'])
            ->findOrFail($taskStepId);
    }

    public function upsertAnswer(TaskStep $taskStep, int $questionId, array $payload): void
    {
        $taskStep->answers()->updateOrCreate(
            ['question_id' => $questionId],
            [
                'answer' => (bool) $payload['answer'],
                'remark' => $payload['remark'] ?? null,
                'submitted_by' => $payload['submitted_by'],
            ]
        );
    }

    public function markCompleted(TaskStep $taskStep, int $completedByUserId): TaskStep
    {
        $taskStep->update([
            'status' => TaskStepStatus::Completed->value,
            'completed_by' => $completedByUserId,
            'completed_at' => now(),
        ]);

        return $taskStep->refresh();
    }

    public function updateAssignment(
        TaskStep $taskStep,
        int $assigneeId,
        int $assignedByUserId,
        bool $managerOverride
    ): TaskStep {
        $taskStep->update([
            'status' => TaskStepStatus::Assigned->value,
            'assigned_to' => $assigneeId,
            'assigned_by' => $assignedByUserId,
            'assigned_at' => now(),
            'manager_override' => $managerOverride,
        ]);

        return $taskStep->refresh();
    }

    public function recordAssignmentHistory(
        TaskStep $taskStep,
        ?int $fromUserId,
        int $toUserId,
        int $assignedByUserId,
        ?string $reason,
        bool $isReassignment
    ): void {
        $taskStep->assignments()->create([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'assigned_by' => $assignedByUserId,
            'reason' => $reason,
            'is_reassignment' => $isReassignment,
            'created_at' => now(),
        ]);
    }

}
