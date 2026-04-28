<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        return response()->json(
            $project->workflows()->with('steps')->orderByDesc('version')->get()
        );
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $nextVersion = (int) $project->workflows()->max('version') + 1;

        $workflow = $project->workflows()->create([
            'name' => $data['name'],
            'version' => $nextVersion,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'is_active' => true,
        ]);

        return response()->json($workflow, 201);
    }

    public function addStep(Request $request, Workflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'step_order' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $step = $workflow->steps()->create($data + ['is_active' => true]);

        return response()->json($step, 201);
    }

    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $workflow->update($data);

        return response()->json($workflow->fresh());
    }

    public function updateStep(Request $request, WorkflowStep $step): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'team_id' => ['sometimes', 'exists:teams,id'],
        ]);

        $step->update($data);

        return response()->json($step->fresh());
    }

    public function attachQuestionSet(Request $request, WorkflowStep $step): JsonResponse
    {
        $data = $request->validate([
            'question_set_id' => ['required', 'exists:question_sets,id'],
        ]);

        $step->questionSets()->syncWithoutDetaching([$data['question_set_id']]);

        return response()->json(['message' => 'Question set linked to workflow step.']);
    }
}
