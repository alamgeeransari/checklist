<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CompanySettingsController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\QuestionSetController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\UserNotificationController;
use App\Http\Controllers\Api\V1\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('otp/send', [AuthController::class, 'sendOtp']);
        Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
    });

    Route::middleware('auth:api')->group(function (): void {
        Route::get('dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('dashboard/charts/releases-by-status', [DashboardController::class, 'releasesByStatus']);
        Route::get('dashboard/charts/monthly-releases', [DashboardController::class, 'monthlyReleases']);
        Route::get('dashboard/charts/team-step-load', [DashboardController::class, 'teamStepLoad']);
        Route::get('dashboard/charts/role-release-view', [DashboardController::class, 'roleReleaseView']);
        Route::get('dashboard/project-options', [DashboardController::class, 'projectOptions']);

        Route::get('company-settings', [CompanySettingsController::class, 'show']);
        Route::patch('company-settings', [CompanySettingsController::class, 'update']);
        Route::post('company-settings/toggle-mail', [CompanySettingsController::class, 'toggleMail']);

        Route::apiResource('companies', CompanyController::class)->only(['index', 'store']);
        Route::patch('companies/{company}/approve', [CompanyController::class, 'approve']);
        Route::patch('companies/{company}/suspend', [CompanyController::class, 'suspend']);

        Route::apiResource('projects', ProjectController::class)->only(['index', 'store', 'show', 'update']);
        Route::patch('projects/{project}/toggle', [ProjectController::class, 'toggle']);
        Route::post('projects/{project}/members', [ProjectController::class, 'addMember']);
        Route::post('projects/{project}/question-sets/{questionSet}', [ProjectController::class, 'attachQuestionSet']);

        Route::get('projects/{project}/teams', [TeamController::class, 'index']);
        Route::post('projects/{project}/teams', [TeamController::class, 'store']);
        Route::post('teams/{team}/members', [TeamController::class, 'addMember']);
        Route::patch('teams/{team}/members/{user}/toggle', [TeamController::class, 'toggleMember']);

        Route::get('projects/{project}/workflows', [WorkflowController::class, 'index']);
        Route::post('projects/{project}/workflows', [WorkflowController::class, 'store']);
        Route::post('workflows/{workflow}/steps', [WorkflowController::class, 'addStep']);
        Route::patch('workflow-steps/{step}', [WorkflowController::class, 'updateStep']);
        Route::post('workflow-steps/{step}/question-sets', [WorkflowController::class, 'attachQuestionSet']);

        Route::apiResource('question-sets', QuestionSetController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('question-sets/{questionSet}/questions', [QuestionSetController::class, 'addQuestion']);
        Route::patch('questions/{question}', [QuestionSetController::class, 'updateQuestion']);
        Route::delete('questions/{question}', [QuestionSetController::class, 'destroyQuestion']);

        Route::apiResource('tasks', TaskController::class)->only(['store', 'show']);
        Route::post('tasks/{task}/start', [TaskController::class, 'start']);
        Route::post('tasks/{task}/steps/{step}/assign', [TaskController::class, 'assign']);
        Route::post('tasks/{task}/steps/{step}/reassign', [TaskController::class, 'reassign']);
        Route::post('tasks/{task}/steps/{step}/submit-checklist', [TaskController::class, 'submitChecklist']);
        Route::post('tasks/{task}/approve-release', [TaskController::class, 'approveRelease']);
        Route::post('tasks/{task}/reject-and-restart', [TaskController::class, 'rejectAndRestart']);
        Route::post('tasks/{task}/release-notes', [TaskController::class, 'storeReleaseNote']);
        Route::get('tasks/{task}/history', [TaskController::class, 'history']);
        Route::get('tasks/{task}/audit-logs', [TaskController::class, 'auditLogs']);

        Route::get('me/notifications', [UserNotificationController::class, 'index']);
        Route::get('me/notifications/unread-count', [UserNotificationController::class, 'unreadCount']);
        Route::post('me/notifications/{notification}/read', [UserNotificationController::class, 'markRead']);
        Route::post('me/notifications/read-all', [UserNotificationController::class, 'markAllRead']);
        Route::get('me/notification-preferences', [UserNotificationController::class, 'preferences']);
        Route::patch('me/notification-preferences', [UserNotificationController::class, 'updatePreferences']);
    });
});
