<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use App\Models\TaskCycle;

interface TaskRepositoryInterface
{
    public function create(array $attributes): Task;

    public function findByIdWithWorkflow(int $taskId): ?Task;

    public function createCycle(Task $task, int $cycleNo, int $startedBy, ?string $restartReason = null): TaskCycle;
}
