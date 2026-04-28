<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionSetController extends Controller
{
    public function update(Request $request, QuestionSet $questionSet): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_reusable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $questionSet->update($data);

        return response()->json($questionSet->refresh());
    }

    public function destroy(QuestionSet $questionSet): JsonResponse
    {
        $questionSet->delete();

        return response()->json([], 204);
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()?->company_id;

        $sets = QuestionSet::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->with('questions')
            ->latest()
            ->paginate();

        return response()->json($sets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_reusable' => ['sometimes', 'boolean'],
        ]);

        $data['company_id'] = $request->user()?->company_id;
        $data['created_by'] = $request->user()?->id;
        $data['is_active'] = true;

        $questionSet = QuestionSet::query()->create($data);

        return response()->json($questionSet, 201);
    }

    public function addQuestion(Request $request, QuestionSet $questionSet): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string'],
            'answer_type' => ['required', 'in:yes_no'],
            'remarks_required_on_no' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $question = $questionSet->questions()->create($data);

        return response()->json($question, 201);
    }

    public function updateQuestion(Request $request, Question $question): JsonResponse
    {
        $data = $request->validate([
            'text' => ['sometimes', 'string'],
            'answer_type' => ['sometimes', 'in:yes_no'],
            'remarks_required_on_no' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $question->update($data);

        return response()->json($question->refresh());
    }

    public function destroyQuestion(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json(['message' => 'Question deleted.']);
    }

    public function attachToProject(Project $project, QuestionSet $questionSet): JsonResponse
    {
        $project->questionSets()->syncWithoutDetaching([$questionSet->id]);

        return response()->json(['message' => 'Question set attached to project.']);
    }
}
