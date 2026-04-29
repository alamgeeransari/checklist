<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Project;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestTenantSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@test.local'],
            [
                'company_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('Password@123'),
                'is_active' => true,
            ]
        );

        $company = Company::query()->firstOrCreate(
            ['slug' => 'acme-corp'],
            [
                'name' => 'Acme Corp',
                'status' => CompanyStatus::Approved->value,
                'approved_by' => $superAdmin->id,
                'approved_at' => now(),
            ]
        );

        $project = Project::query()->firstOrCreate(
            ['company_id' => $company->id, 'code' => 'REL-CHECK'],
            [
                'name' => 'Release Checklist Platform',
                'description' => 'Primary project for release checklist workflow validation.',
                'is_active' => true,
            ]
        );

        $companyAdmin = $this->makeUser($company->id, 'Company Admin', 'companyadmin@acme.local');
        $manager = $this->makeUser($company->id, 'Manager', 'manager@acme.local');
        $technicalManager = $this->makeUser($company->id, 'Technical Manager', 'techmanager@acme.local');
        $teamLead = $this->makeUser($company->id, 'Team Lead', 'teamlead@acme.local');
        $teamMemberOne = $this->makeUser($company->id, 'Team Member One', 'teammember1@acme.local');
        $teamMemberTwo = $this->makeUser($company->id, 'Team Member Two', 'teammember2@acme.local');
        $qaUser = $this->makeUser($company->id, 'QA Analyst', 'qa@acme.local');
        $baUser = $this->makeUser($company->id, 'Business Analyst', 'ba@acme.local');
        $pmUser = $this->makeUser($company->id, 'Project Manager', 'pm@acme.local');
        $infraUser = $this->makeUser($company->id, 'Infra Engineer', 'infra@acme.local');

        $usersForProject = [
            $companyAdmin,
            $manager,
            $technicalManager,
            $teamLead,
            $teamMemberOne,
            $teamMemberTwo,
            $qaUser,
            $baUser,
            $pmUser,
            $infraUser,
        ];

        foreach ($usersForProject as $user) {
            $project->users()->syncWithoutDetaching([
                $user->id => [
                    'joined_at' => now(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            UserNotificationPreference::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'mail_enabled' => true,
                    'push_enabled' => true,
                    'task_created_enabled' => true,
                    'step_assigned_enabled' => true,
                    'step_completed_enabled' => true,
                    'task_restarted_enabled' => true,
                ]
            );
        }

        $this->assignRole($superAdmin, 'Super Admin', null, null);
        $this->assignRole($companyAdmin, 'Company Admin', $company->id, null);
        $this->assignRole($manager, 'Manager', $company->id, $project->id);
        $this->assignRole($technicalManager, 'Technical Manager', $company->id, $project->id);
        $this->assignRole($teamLead, 'Team Lead', $company->id, $project->id);
        $this->assignRole($teamMemberOne, 'Team Member', $company->id, $project->id);
        $this->assignRole($teamMemberTwo, 'Team Member', $company->id, $project->id);
        $this->assignRole($qaUser, 'Team Member', $company->id, $project->id);
        $this->assignRole($baUser, 'Team Member', $company->id, $project->id);
        $this->assignRole($pmUser, 'Team Member', $company->id, $project->id);
        $this->assignRole($infraUser, 'Team Member', $company->id, $project->id);

        $this->createTeam($project, 'QA', [$qaUser], [$qaUser->id]);
        $this->createTeam($project, 'Dev Lead', [$teamLead, $teamMemberOne, $teamMemberTwo], [$teamLead->id]);
        $this->createTeam($project, 'BA', [$baUser], [$baUser->id]);
        $this->createTeam($project, 'Tech Manager', [$technicalManager], [$technicalManager->id]);
        $this->createTeam($project, 'PM', [$pmUser], [$pmUser->id]);
        $this->createTeam($project, 'Infra', [$infraUser], [$infraUser->id]);

        CompanySetting::query()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'ui_theme' => 'light',
                'primary_color' => '#2563EB',
                'mail_notifications_enabled' => true,
            ]
        );

    }

    private function makeUser(?int $companyId, string $name, string $email): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'company_id' => $companyId,
                'name' => $name,
                'password' => Hash::make('Password@123'),
                'is_active' => true,
            ]
        );
    }

    private function assignRole(User $user, string $roleName, ?int $companyId, ?int $projectId): void
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        UserRole::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'company_id' => $companyId,
                'project_id' => $projectId,
            ],
            []
        );
    }

    /**
     * @param array<int, \App\Models\User> $members
     * @param array<int, int> $leadUserIds
     */
    private function createTeam(Project $project, string $name, array $members, array $leadUserIds): Team
    {
        $team = Team::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => $name],
            [
                'description' => "{$name} team",
                'is_active' => true,
            ]
        );

        foreach ($members as $member) {
            $team->members()->syncWithoutDetaching([
                $member->id => [
                    'is_lead' => in_array($member->id, $leadUserIds, true),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        return $team;
    }

}
