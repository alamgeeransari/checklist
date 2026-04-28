<?php

namespace App\Repositories\Contracts;

use App\Models\TaskStep;

interface TaskStepRepositoryInterface
{
    public function findByIdOrFail(int $taskStepId): TaskStep;

    public function updateAssignment(
        TaskStep $taskStep,
        int $assigneeId,
        int $assignedById,
        bool $override
    ): TaskStep;

    public function recordAssignmentHistory(
        TaskStep $taskStep,
        ?int $fromUserId,
        int $toUserId,
        int $assignedById,
        ?string $reason,
        bool $isReassignment
    ): void;

    /**
     * @param array{answer:bool, remark:?string, submitted_by:int} $payload
     */
    public function upsertAnswer(TaskStep $taskStep, int $questionId, array $payload): void;

    public function markCompleted(TaskStep $taskStep, int $completedByUserId): TaskStep;
}
