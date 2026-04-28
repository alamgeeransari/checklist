<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        return response()->json(
            $project->teams()->with('members')->get()
        );
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $team = $project->teams()->create($data);

        return response()->json($team, 201);
    }

    public function addMember(Request $request, Team $team): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'is_lead' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $team->members()->syncWithoutDetaching([
            $data['user_id'] => [
                'is_lead' => (bool) ($data['is_lead'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return response()->json(['message' => 'Member added to team.']);
    }

    public function toggleMember(Team $team, int $user): JsonResponse
    {
        $existing = $team->members()->where('users.id', $user)->firstOrFail();
        $isActive = (bool) ($existing->pivot->is_active ?? false);

        $team->members()->updateExistingPivot($user, [
            'is_active' => ! $isActive,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Team member status updated.']);
    }
}
