<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectUserRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users to assign to projects
        $projectManager = User::byRole('project_manager')->first();
        $architects = User::byRole('architect')->pluck('id')->toArray();
        $engineers = User::byRole('engineer')->pluck('id')->toArray();
        $admin = User::byRole('admin')->first();

        // Get first 5 clients (or create new ones)
        $clients = Client::limit(5)->get();
        if ($clients->count() < 5) {
            $clients = Client::factory(5)->create([
                'account_owner_id' => $projectManager->id,
            ]);
        }

        // Create 5 projects with tasks and team assignments
        $clients->each(function ($client, $index) use ($projectManager, $architects, $engineers, $admin) {
            // Create project
            $project = Project::factory()
                ->active()
                ->create([
                    'budget' => [150000, 250000, 350000, 450000, 500000][$index],
                ]);

            // Attach client to project (many-to-many relationship)
            $project->clients()->attach($client->id);

            // Add project manager to project team
            ProjectUserRole::create([
                'project_id' => $project->id,
                'user_id' => $projectManager->id,
                'base_role' => 'admin',
                'specialty_role' => 'pm',
            ]);

            // Add architect to project team
            if (!empty($architects)) {
                $architectId = $architects[array_rand($architects)];
                ProjectUserRole::create([
                    'project_id' => $project->id,
                    'user_id' => $architectId,
                    'base_role' => 'member',
                    'specialty_role' => 'architect',
                ]);
            }

            // Add 2-3 engineers to project team
            $engineerCount = rand(2, 3);
            $selectedEngineers = array_slice($engineers, 0, $engineerCount);
            foreach ($selectedEngineers as $engineerId) {
                ProjectUserRole::create([
                    'project_id' => $project->id,
                    'user_id' => $engineerId,
                    'base_role' => 'member',
                    'specialty_role' => 'engineer',
                ]);
            }

            // Create kanban tasks for the project
            // TODO tasks (5)
            Task::factory(5)
                ->todo()
                ->create([
                    'project_id' => $project->id,
                    'assigned_to' => $engineers[array_rand($engineers)] ?? $projectManager->id,
                    'priority' => fake()->randomElement(['low', 'medium', 'high']),
                ]);

            // IN-PROGRESS tasks (3-4)
            Task::factory(rand(3, 4))
                ->inProgress()
                ->create([
                    'project_id' => $project->id,
                    'assigned_to' => $engineers[array_rand($engineers)] ?? $projectManager->id,
                    'priority' => 'high',
                ]);

            // DONE tasks (4-6)
            Task::factory(rand(4, 6))
                ->done()
                ->create([
                    'project_id' => $project->id,
                    'assigned_to' => $engineers[array_rand($engineers)] ?? $projectManager->id,
                ]);

            // Update project progress based on tasks
            $this->updateProjectProgress($project);
        });
    }

    /**
     * Update project progress based on completed tasks
     */
    private function updateProjectProgress(Project $project): void
    {
        $totalTasks = $project->tasks()->count();
        if ($totalTasks === 0) {
            return;
        }

        $completedTasks = $project->tasks()->where('status', 'done')->count();
        $progress = (int) (($completedTasks / $totalTasks) * 100);

        $project->update(['progress' => $progress]);
    }
}
