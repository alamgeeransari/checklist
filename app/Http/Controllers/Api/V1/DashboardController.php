<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json($this->dashboard($request));
    }

    public function releasesByStatus(Request $request): JsonResponse
    {
        $dashboard = $this->dashboard($request);

        return response()->json($dashboard['charts']['releases_by_status']);
    }

    public function monthlyReleases(Request $request): JsonResponse
    {
        $dashboard = $this->dashboard($request);

        return response()->json($dashboard['charts']['releases_over_time']);
    }

    public function teamStepLoad(Request $request): JsonResponse
    {
        $dashboard = $this->dashboard($request);

        return response()->json($dashboard['charts']['team_step_load']);
    }

    public function roleReleaseView(Request $request): JsonResponse
    {
        $dashboard = $this->dashboard($request);

        return response()->json([
            'roles' => $dashboard['meta']['roles'],
            'summary' => $dashboard['summary'],
            'projects' => $dashboard['charts']['releases_by_project'],
        ]);
    }

    public function projectOptions(Request $request): JsonResponse
    {
        return response()->json(
            $this->dashboardService->projectOptionsFor($request->user())
        );
    }

    private function dashboard(Request $request): array
    {
        return $this->dashboardService->buildReleaseDashboard($request->user(), [
            'project_id' => $request->integer('project_id'),
            'range_days' => $request->integer('range_days', 30),
        ]);
    }
}
