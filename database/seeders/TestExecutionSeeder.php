<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\TaskStepStatus;
use App\Models\AuditLog;
use App\Models\EmailOtp;
use App\Models\Project;
use App\Models\Question;
use App\Models\ReleaseNote;
use App\Models\Task;
use App\Models\TaskCycle;
use App\Models\TaskStep;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestExecutionSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()
            ->where('code', 'REL-CHECK')
            ->first();

        if (! $project) {
            return;
        }

        $workflow = Workflow::query()
            ->where('project_id', $project->id)
            ->where('is_default', true)
            ->first();

        if (! $workflow) {
            return;
        }

        $manager = User::query()->where('email', 'manager@acme.local')->first();
        if (! $manager) {
            return;
        }

        $task = Task::query()->firstOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Release 1.0 - Payment Gateway Stabilization',
            ],
            [
                'company_id' => $project->company_id,
                'workflow_id' => $workflow->id,
                'description' => 'Validate deployment checklist across QA, Dev Lead, BA, Tech Manager, PM, and Infra.',
                'release_tag' => 'v1.0.0',
                'status' => TaskStatus::InProgress->value,
                'created_by' => $manager->id,
                'current_cycle_no' => 1,
                'is_active' => true,
            ]
        );

        $cycle = TaskCycle::query()->firstOrCreate(
            [
                'task_id' => $task->id,
                'cycle_no' => 1,
            ],
            [
                'started_by' => $manager->id,
                'restart_reason' => null,
                'started_at' => now()->subDay(),
                'ended_at' => null,
            ]
        );

        $workflowSteps = WorkflowStep::query()
            ->where('workflow_id', $workflow->id)
            ->with('team.members')
            ->orderBy('step_order')
            ->get();

        foreach ($workflowSteps as $workflowStep) {
            /** @var TaskStep $taskStep */
            $taskStep = TaskStep::query()->firstOrCreate(
                [
                    'task_cycle_id' => $cycle->id,
                    'workflow_step_id' => $workflowStep->id,
                ],
                [
                    'team_id' => $workflowStep->team_id,
                    'step_order' => $workflowStep->step_order,
                    'status' => TaskStepStatus::Pending->value,
                    'manager_override' => false,
                ]
            );

            $assignee = $workflowStep->team?->members()->where('users.is_active', true)->orderBy('users.id')->first();
            if ($assignee) {
                $taskStep->update([
                    'assigned_to' => $assignee->id,
                    'assigned_by' => $manager->id,
                    'assigned_at' => now()->subHours(12 - $workflowStep->step_order),
                ]);

                if ($workflowStep->step_order <= 2) {
                    $taskStep->update([
                        'status' => TaskStepStatus::Completed->value,
                        'completed_by' => $assignee->id,
                        'completed_at' => now()->subHours(10 - $workflowStep->step_order),
                    ]);

                    $questions = Question::query()
                        ->whereIn('question_set_id', $workflowStep->questionSets()->pluck('question_sets.id'))
                        ->get();

                    foreach ($questions as $question) {
                        $taskStep->answers()->updateOrCreate(
                            ['question_id' => $question->id],
                            [
                                'answer' => true,
                                'remark' => null,
                                'submitted_by' => $assignee->id,
                            ]
                        );
                    }
                } elseif ($workflowStep->step_order === 3) {
                    $taskStep->update([
                        'status' => TaskStepStatus::Assigned->value,
                    ]);
                } else {
                    $taskStep->update([
                        'status' => TaskStepStatus::Pending->value,
                    ]);
                }
            }
        }

        $task->update(['status' => TaskStatus::InProgress->value]);

        ReleaseNote::query()->firstOrCreate(
            [
                'task_id' => $task->id,
                'author_id' => $manager->id,
            ],
            [
                'notes' => 'Initial release notes seeded for QA/UAT validation scenario.',
            ]
        );

        AuditLog::query()->firstOrCreate(
            [
                'action' => 'workflow.task_created',
                'entity_type' => Task::class,
                'entity_id' => $task->id,
            ],
            [
                'company_id' => $project->company_id,
                'project_id' => $project->id,
                'user_id' => $manager->id,
                'before_data' => null,
                'after_data' => ['status' => TaskStatus::InProgress->value],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'seeder',
                'created_at' => now()->subDay(),
            ]
        );

        EmailOtp::query()->updateOrCreate(
            ['user_id' => $manager->id],
            [
                'otp_hash' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(30),
                'verified_at' => null,
                'attempts' => 0,
            ]
        );
    }
}
