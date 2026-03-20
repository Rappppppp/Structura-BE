<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTeamAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_team_member_cannot_view_project_team(): void
    {
        $owner = User::factory()->engineer()->create();
        $viewer = User::factory()->architect()->create();
        $project = $this->createProjectForOwner($owner);

        $project->projectUserRoles()->create([
            'user_id' => $owner->id,
            'role' => 'Project Manager',
        ]);

        $response = $this
            ->actingAs($viewer, 'api')
            ->getJson("/api/projects/{$project->id}/team");

        $response->assertForbidden();
    }

    public function test_project_manager_can_add_team_member(): void
    {
        $manager = User::factory()->projectManager()->create();
        $newMember = User::factory()->create();
        $project = $this->createProjectForOwner($manager);

        $response = $this
            ->actingAs($manager, 'api')
            ->postJson("/api/projects/{$project->id}/team", [
                'user_id' => $newMember->id,
                'role' => 'Architect',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('project_user_roles', [
            'project_id' => $project->id,
            'user_id' => $newMember->id,
            'role' => 'Architect',
        ]);
    }

    public function test_remove_returns_not_found_when_user_is_not_part_of_project_team(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $project = $this->createProjectForOwner($admin);

        $response = $this
            ->actingAs($admin, 'api')
            ->deleteJson("/api/projects/{$project->id}/team/{$member->id}");

        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => 'User is not a member of this project',
            ]);
    }

    private function createProjectForOwner(User $owner): Project
    {
        $client = Client::factory()->create([
            'account_owner_id' => $owner->id,
        ]);

        return Project::create([
            'name' => 'Enterprise Project',
            'description' => 'Project for authorization tests',
            'client_id' => $client->id,
            'budget' => 100000,
            'progress' => 0,
            'status' => 'active',
            'deadline_at' => now()->addMonth(),
        ]);
    }
}
