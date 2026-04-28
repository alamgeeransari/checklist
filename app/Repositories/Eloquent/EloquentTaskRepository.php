<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Models\TaskCycle;
use App\Models\TaskStep;
use App\Repositories\Contracts\TaskRepositoryInterface;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function findByIdWithWorkflow(int $taskId): ?Task
    {
        return Task::query()
            ->with(['workflow.steps.team.users', 'cycles.steps.team.users', 'project', 'company'])
            ->find($taskId);
    }

    public function create(array $payload): Task
    {
        return Task::query()->create($payload);
    }

    public function createCycle(Task $task, int $cycleNo, int $startedBy, ?string $restartReason = null): TaskCycle
    {
        return $task->cycles()->create([
            'cycle_no' => $cycleNo,
            'restart_reason' => $restartReason,
            'started_by' => $startedBy,
            'started_at' => now(),
        ]);
    }
}
