<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function buildReleaseDashboard(User $user, array $filters = []): array
    {
        $rangeDays = (int) ($filters['range_days'] ?? 30);
        $rangeDays = $rangeDays > 0 ? min($rangeDays, 365) : 30;

        $projectId = isset($filters['project_id']) ? (int) $filters['project_id'] : null;

        $query = Task::query()
            ->with(['project:id,name,company_id', 'cycles.steps.team'])
            ->where('is_active', true);

        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $roleNames = $user->roles()->pluck('name')->all();
        $isSuperAdmin = in_array(RoleName::SUPER_ADMIN->value, $roleNames, true);
        $isCompanyAdmin = in_array(RoleName::COMPANY_ADMIN->value, $roleNames, true);

        if (! $isSuperAdmin && ! $isCompanyAdmin) {
            $userProjectIds = $user->projects()->pluck('projects.id');
            $query->whereIn('project_id', $userProjectIds);
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        /** @var Collection<int, Task> $tasks */
        $tasks = $query->get();

        $now = Carbon::now();
        $startDate = $now->copy()->subDays($rangeDays - 1)->startOfDay();

        $series = [];
        for ($date = $startDate->copy(); $date->lte($now); $date->addDay()) {
            $series[$date->toDateString()] = 0;
        }

        foreach ($tasks as $task) {
            $createdDate = optional($task->created_at)?->toDateString();
            if ($createdDate && isset($series[$createdDate])) {
                $series[$createdDate]++;
            }
        }

        $statusCounts = $tasks
            ->groupBy(fn (Task $task): string => (string) ($task->status->value ?? $task->status))
            ->map(fn (Collection $group): int => $group->count())
            ->sortKeys()
            ->toArray();

        $projectCounts = $tasks
            ->groupBy('project_id')
            ->map(function (Collection $group): array {
                /** @var Task $first */
                $first = $group->first();

                return [
                    'project_id' => (int) $first->project_id,
                    'project_name' => (string) ($first->project?->name ?? 'Unknown'),
                    'count' => $group->count(),
                ];
            })
            ->values()
            ->all();

        $teamStepLoad = $tasks
            ->flatMap(fn (Task $task) => $task->cycles)
            ->flatMap(fn ($cycle) => $cycle->steps ?? [])
            ->groupBy(fn ($step): string => (string) ($step->team?->name ?? 'Unknown'))
            ->map(fn (Collection $group): int => $group->count())
            ->sortKeys()
            ->toArray();

        return [
            'summary' => [
                'total_releases' => $tasks->count(),
                'released' => $statusCounts['released'] ?? 0,
                'in_progress' => $statusCounts['in_progress'] ?? 0,
                'ready_for_release' => $statusCounts['ready_for_release'] ?? 0,
                'changes_requested' => $statusCounts['changes_requested'] ?? 0,
            ],
            'charts' => [
                'releases_by_status' => $statusCounts,
                'releases_over_time' => $series,
                'releases_by_project' => $projectCounts,
                'team_step_load' => $teamStepLoad,
            ],
            'meta' => [
                'range_days' => $rangeDays,
                'applied_project_id' => $projectId,
                'roles' => $roleNames,
            ],
        ];
    }

    public function projectOptionsFor(User $user): array
    {
        $query = Project::query()->select(['id', 'name', 'company_id'])->where('is_active', true);

        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $roleNames = $user->roles()->pluck('name')->all();
        $isSuperAdmin = in_array(RoleName::SUPER_ADMIN->value, $roleNames, true);
        $isCompanyAdmin = in_array(RoleName::COMPANY_ADMIN->value, $roleNames, true);

        if (! $isSuperAdmin && ! $isCompanyAdmin) {
            $query->whereIn('id', $user->projects()->pluck('projects.id'));
        }

        return $query
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
            ])
            ->all();
    }
}
