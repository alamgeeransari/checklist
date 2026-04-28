<?php

namespace Database\Seeders;

use App\Enums\TaskStepStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\QuestionSet;
use App\Models\Task;
use App\Models\TaskCycle;
use App\Models\TaskStep;
use App\Models\Team;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

class TestWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()
            ->where('code', 'REL-CHECK')
            ->with('company')
            ->first();

        if (! $project) {
            return;
        }

        $workflow = Workflow::query()->firstOrCreate(
            [
                'project_id' => $project->id,
                'version' => 1,
            ],
            [
                'name' => 'Default Release Approval Workflow',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $teamOrder = [
            ['name' => 'QA', 'step_name' => 'QA Validation'],
            ['name' => 'Dev Lead', 'step_name' => 'Development Lead Review'],
            ['name' => 'BA', 'step_name' => 'Business Analyst Review'],
            ['name' => 'Tech Manager', 'step_name' => 'Technical Manager Approval'],
            ['name' => 'PM', 'step_name' => 'Project Manager Approval'],
            ['name' => 'Infra', 'step_name' => 'Infrastructure Readiness'],
        ];

        $questionSet = QuestionSet::query()->firstOrCreate(
            [
                'company_id' => $project->company_id,
                'name' => 'Core Release Checklist',
            ],
            [
                'description' => 'Reusable checklist set for release gates.',
                'created_by' => User::query()->where('email', 'manager@acme.local')->value('id'),
                'is_reusable' => true,
                'is_active' => true,
            ]
        );

        $questions = [
            [1, 'Build pipeline completed successfully?'],
            [2, 'Automated test suite passed?'],
            [3, 'Rollback strategy documented and verified?'],
        ];

        foreach ($questions as [$sortOrder, $text]) {
            $questionSet->questions()->updateOrCreate(
                ['sort_order' => $sortOrder],
                [
                    'text' => $text,
                    'answer_type' => 'yes_no',
                    'remarks_required_on_no' => true,
                    'is_active' => true,
                ]
            );
        }

        $project->questionSets()->syncWithoutDetaching([$questionSet->id]);

        foreach ($teamOrder as $index => $config) {
            $team = Team::query()
                ->where('project_id', $project->id)
                ->where('name', $config['name'])
                ->first();

            if (! $team) {
                continue;
            }

            $step = WorkflowStep::query()->updateOrCreate(
                [
                    'workflow_id' => $workflow->id,
                    'step_order' => $index + 1,
                ],
                [
                    'team_id' => $team->id,
                    'name' => $config['step_name'],
                    'description' => "Step ".($index + 1)." review by {$config['name']} team.",
                    'is_active' => true,
                ]
            );

            $step->questionSets()->syncWithoutDetaching([$questionSet->id]);
        }

        $manager = User::query()->where('email', 'manager@acme.local')->first();
        if (! $manager) {
            return;
        }

        $task = Task::query()->firstOrCreate(
            ['project_id' => $project->id, 'title' => 'Release v1.0.0'],
            [
                'company_id' => $project->company_id,
                'workflow_id' => $workflow->id,
                'description' => 'Seeded sample release task for testing workflow transitions.',
                'release_tag' => 'v1.0.0',
                'status' => TaskStatus::InProgress->value,
                'created_by' => $manager->id,
                'current_cycle_no' => 1,
                'is_active' => true,
            ]
        );

        $cycle = TaskCycle::query()->firstOrCreate(
            ['task_id' => $task->id, 'cycle_no' => 1],
            [
                'started_by' => $manager->id,
                'restart_reason' => null,
                'started_at' => now(),
                'ended_at' => null,
            ]
        );

        $steps = $workflow->steps()->orderBy('step_order')->get();

        foreach ($steps as $step) {
            $existing = TaskStep::query()->firstOrCreate(
                [
                    'task_cycle_id' => $cycle->id,
                    'workflow_step_id' => $step->id,
                ],
                [
                    'team_id' => $step->team_id,
                    'step_order' => $step->step_order,
                    'status' => TaskStepStatus::Pending->value,
                ]
            );

            $defaultAssignee = $step->team?->members()->where('users.is_active', true)->orderBy('users.id')->first();
            if ($defaultAssignee && ! $existing->assigned_to) {
                $existing->update([
                    'status' => TaskStepStatus::Assigned->value,
                    'assigned_to' => $defaultAssignee->id,
                    'assigned_by' => $manager->id,
                    'assigned_at' => now(),
                ]);
            }
        }
    }
}
