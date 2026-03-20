<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_project(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create([
            'account_owner_id' => $admin->id,
        ]);

        $response = $this
            ->actingAs($admin, 'api')
            ->postJson('/api/projects', [
                'name' => 'Enterprise Alpha',
                'description' => 'Initial rollout project',
                'client_id' => $client->id,
                'budget' => 150000,
                'status' => 'active',
                'deadline_at' => now()->addMonths(2)->toDateTimeString(),
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('projects', [
            'name' => 'Enterprise Alpha',
            'client_id' => $client->id,
        ]);
    }

    public function test_project_manager_can_update_project(): void
    {
        $manager = User::factory()->projectManager()->create();
        $project = $this->createProjectForUser($manager);

        $response = $this
            ->actingAs($manager, 'api')
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Updated Enterprise Name',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Enterprise Name',
        ]);
    }

    public function test_engineer_cannot_create_project(): void
    {
        $engineer = User::factory()->engineer()->create();
        $client = Client::factory()->create([
            'account_owner_id' => $engineer->id,
        ]);

        $response = $this
            ->actingAs($engineer, 'api')
            ->postJson('/api/projects', [
                'name' => 'Unauthorized Project',
                'client_id' => $client->id,
                'budget' => 100000,
                'status' => 'active',
                'deadline_at' => now()->addMonth()->toDateTimeString(),
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('projects', [
            'name' => 'Unauthorized Project',
        ]);
    }

    private function createProjectForUser(User $owner): Project
    {
        $client = Client::factory()->create([
            'account_owner_id' => $owner->id,
        ]);

        return Project::create([
            'name' => 'Original Project',
            'description' => 'Project used in authorization update test',
            'client_id' => $client->id,
            'budget' => 200000,
            'progress' => 0,
            'status' => 'active',
            'deadline_at' => now()->addMonths(3),
        ]);
    }
}
