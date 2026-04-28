<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\QuestionSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::query()->with(['company', 'users']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $project = Project::query()->create($validated);

        return response()->json($project, 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(
            $project->load(['company', 'users', 'teams', 'workflows'])
        );
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $project->update($validated);

        return response()->json($project->fresh());
    }

    public function toggle(Project $project): JsonResponse
    {
        $project->update(['is_active' => ! $project->is_active]);

        return response()->json($project->fresh());
    }

    public function addMember(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $project->users()->syncWithoutDetaching([
            $validated['user_id'] => [
                'is_active' => $validated['is_active'] ?? true,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return response()->json(['message' => 'Member linked to project.']);
    }

    public function attachQuestionSet(Project $project, QuestionSet $questionSet): JsonResponse
    {
        $project->questionSets()->syncWithoutDetaching([$questionSet->id]);

        return response()->json(['message' => 'Question set linked to project.']);
    }
}
