<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTaskStepRequest;
use App\Http\Requests\SubmitChecklistRequest;
use App\Http\Requests\TaskStoreRequest;
use App\Models\AuditLog;
use App\Models\ReleaseNote;
use App\Models\Task;
use App\Models\TaskStep;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\WorkflowEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private readonly WorkflowEngineService $workflowEngineService,
        private readonly AssignmentService $assignmentService
    ) {
    }

    public function store(TaskStoreRequest $request): JsonResponse
    {
        $task = $this->workflowEngineService->createTask([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($task, 201);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load([
            'project',
            'workflow.steps.team',
            'cycles.steps.assignee',
            'releaseNotes.author',
        ]);

        return response()->json($task);
    }

    public function start(Task $task, Request $request): JsonResponse
    {
        $this->workflowEngineService->startTask($task, $request->user());

        return response()->json(['message' => 'Task started']);
    }

    public function assign(Task $task, TaskStep $step, AssignTaskStepRequest $request): JsonResponse
    {
        abort_if($step->cycle->task_id !== $task->id, 404);

        $assignee = User::query()->findOrFail($request->validated('to_user_id'));
        $override = (bool) ($request->validated('override') ?? false);
        $this->assignmentService->assignStep($step, $assignee, $request->user(), $override);

        return response()->json(['message' => 'Step assigned']);
    }

    public function reassign(Task $task, TaskStep $step, AssignTaskStepRequest $request): JsonResponse
    {
        abort_if($step->cycle->task_id !== $task->id, 404);

        $assignee = User::query()->findOrFail($request->validated('to_user_id'));
        $this->assignmentService->reassignStep(
            $step,
            $assignee,
            $request->user(),
            $request->validated('reason'),
            (bool) ($request->validated('override') ?? false)
        );

        return response()->json(['message' => 'Step reassigned']);
    }

    public function submitChecklist(Task $task, TaskStep $step, SubmitChecklistRequest $request): JsonResponse
    {
        abort_if($step->cycle->task_id !== $task->id, 404);

        $this->workflowEngineService->completeStep($step, $request->validated('answers'), $request->user());

        return response()->json(['message' => 'Checklist submitted']);
    }

    public function rejectAndRestart(Task $task, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $this->workflowEngineService->rejectAndRestart($task, $request->string('reason')->toString(), $request->user());

        return response()->json(['message' => 'Task restarted from first step']);
    }

    public function approveRelease(Task $task): JsonResponse
    {
        $task->update(['status' => TaskStatus::Released->value]);

        return response()->json([
            'message' => 'Task approved for release.',
            'task' => $task->refresh(),
        ]);
    }

    public function storeReleaseNote(Task $task, Request $request): JsonResponse
    {
        $data = $request->validate([
            'notes' => ['required', 'string', 'min:3'],
        ]);

        $note = ReleaseNote::query()->create([
            'task_id' => $task->id,
            'author_id' => $request->user()->id,
            'notes' => $data['notes'],
        ]);

        return response()->json($note, 201);
    }

    public function history(Task $task): JsonResponse
    {
        $history = $task->cycles()
            ->with([
                'starter:id,name,email',
                'steps.workflowStep:id,name',
                'steps.assignee:id,name,email',
                'steps.completedBy:id,name,email',
                'steps.assignments',
                'steps.answers',
            ])
            ->orderBy('cycle_no')
            ->get();

        return response()->json($history);
    }

    public function auditLogs(Task $task): JsonResponse
    {
        $logs = AuditLog::query()
            ->where('company_id', $task->company_id)
            ->where('project_id', $task->project_id)
            ->where(function ($query) use ($task): void {
                $query->where(function ($taskQuery) use ($task): void {
                    $taskQuery
                        ->where('entity_type', Task::class)
                        ->where('entity_id', $task->id);
                })->orWhere(function ($stepQuery) use ($task): void {
                    $stepIds = $task->cycles()
                        ->with('steps:id,task_cycle_id')
                        ->get()
                        ->flatMap(fn ($cycle) => $cycle->steps)
                        ->pluck('id')
                        ->all();

                    $stepQuery
                        ->where('entity_type', TaskStep::class)
                        ->whereIn('entity_id', $stepIds);
                });
            })
            ->latest('id')
            ->paginate(50);

        return response()->json($logs);
    }
}
