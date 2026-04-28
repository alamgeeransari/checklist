<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\TaskStepStatus;
use App\Events\TaskCreated;
use App\Events\StepCompleted;
use App\Events\TaskRestarted;
use App\Models\Task;
use App\Models\TaskCycle;
use App\Models\TaskStep;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorkflowEngineService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly AssignmentService $assignmentService,
        private readonly ChecklistService $checklistService,
    ) {
    }

    public function createTask(array $attributes): Task
    {
        /** @var Task $task */
        $task = Task::query()->create([
            ...$attributes,
            'status' => TaskStatus::Draft->value,
        ]);

        event(new TaskCreated($task));

        return $task->refresh();
    }

    public function startTask(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor): Task {
            if (! in_array($task->status, [TaskStatus::Draft, TaskStatus::ChangesRequested], true)) {
                throw new RuntimeException('Only draft or changes requested tasks can be started.');
            }

            $cycleNo = (int) $task->current_cycle_no;

            $cycle = TaskCycle::query()->create([
                'task_id' => $task->id,
                'cycle_no' => $cycleNo,
                'started_by' => $actor->id,
                'started_at' => now(),
            ]);

            $steps = $task->workflow->steps()->orderBy('step_order')->get();
            foreach ($steps as $step) {
                TaskStep::query()->create([
                    'task_cycle_id' => $cycle->id,
                    'workflow_step_id' => $step->id,
                    'team_id' => $step->team_id,
                    'step_order' => $step->step_order,
                    'status' => TaskStepStatus::Pending->value,
                ]);
            }

            $task->update(['status' => TaskStatus::InProgress->value]);

            $firstStep = $cycle->steps()->orderBy('step_order')->first();
            if (! $firstStep) {
                throw new RuntimeException('No workflow steps found for task.');
            }

            $defaultAssignee = $firstStep->team->members()->where('users.is_active', true)->orderBy('users.id')->first();
            if ($defaultAssignee) {
                $this->assignmentService->assignStep($firstStep, $defaultAssignee, $actor);
            }

            return $task->refresh();
        });
    }

    public function completeStep(TaskStep $taskStep, array $answers, User $actor): TaskStep
    {
        return DB::transaction(function () use ($taskStep, $answers, $actor): TaskStep {
            if (! in_array($taskStep->status, [TaskStepStatus::Assigned, TaskStepStatus::InProgress], true)) {
                throw new RuntimeException('Step is not in an actionable state.');
            }

            $taskStep = $this->checklistService->submit($taskStep, $answers, $actor);
            $taskStep = $taskStep->refresh(['cycle', 'team']);
            event(new StepCompleted($taskStep, $actor));

            $cycle = $taskStep->cycle()->with('task', 'steps.team.users')->firstOrFail();
            $nextStep = $cycle->steps()
                ->where('step_order', '>', $taskStep->step_order)
                ->where('status', TaskStepStatus::Pending->value)
                ->orderBy('step_order')
                ->first();

            if ($nextStep) {
                $assignee = $nextStep->team->members()->where('users.is_active', true)->orderBy('users.id')->first();
                if ($assignee) {
                    $this->assignmentService->assignStep($nextStep, $assignee, $actor);
                }
            } else {
                $cycle->task->update(['status' => TaskStatus::ReadyForRelease->value]);
            }

            return $taskStep->refresh();
        });
    }

    public function rejectAndRestart(Task $task, string $reason, User $actor): Task
    {
        return DB::transaction(function () use ($task, $reason, $actor): Task {
            /** @var TaskCycle|null $currentCycle */
            $currentCycle = $task->cycles()
                ->where('cycle_no', $task->current_cycle_no)
                ->orderByDesc('id')
                ->first();

            if ($currentCycle) {
                $currentCycle->update(['ended_at' => now()]);
            }

            $newCycleNo = $task->current_cycle_no + 1;
            $task->update([
                'status' => TaskStatus::InProgress->value,
                'current_cycle_no' => $newCycleNo,
            ]);

            $newCycle = TaskCycle::query()->create([
                'task_id' => $task->id,
                'cycle_no' => $newCycleNo,
                'started_by' => $actor->id,
                'restart_reason' => $reason,
                'started_at' => now(),
            ]);

            $steps = $task->workflow->steps()->orderBy('step_order')->get();
            foreach ($steps as $step) {
                TaskStep::query()->create([
                    'task_cycle_id' => $newCycle->id,
                    'workflow_step_id' => $step->id,
                    'team_id' => $step->team_id,
                    'step_order' => $step->step_order,
                    'status' => TaskStepStatus::Pending->value,
                ]);
            }

            $firstStep = $newCycle->steps()->orderBy('step_order')->first();
            if ($firstStep) {
                $defaultAssignee = $firstStep->team->members()->where('users.is_active', true)->orderBy('users.id')->first();
                if ($defaultAssignee) {
                    $this->assignmentService->assignStep($firstStep, $defaultAssignee, $actor);
                }
            }

            event(new TaskRestarted($task->refresh(), $reason, $actor));

            return $task->refresh();
        });
    }
}
