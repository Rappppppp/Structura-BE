<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define a simple admin gate based on `role` column.
        Gate::define('admin', function (User $user): bool {
            return isset($user->role) && strtolower((string) $user->role) === 'admin';
        });

        Gate::define('update', function (User $user, Project $project): bool {
            // Admin can always update
            if (strtolower((string) $user->role) === 'admin') {
                return true;
            }

            // Project manager can update
            if (strtolower((string) $user->role) === 'project_manager') {
                return true;
            }

            // Check if user is part of the project team with manager/lead role
            $projectRole = $project->projectUserRoles()
                ->where('user_id', $user->id)
                ->value('role');

            if (! is_string($projectRole)) {
                return false;
            }

            $normalizedRole = strtolower($projectRole);

            return str_contains($normalizedRole, 'manager') || str_contains($normalizedRole, 'lead');
        });

        Gate::define('view', function (User $user, Project $project): bool {
            // Admin can always view
            if (strtolower((string) $user->role) === 'admin') {
                return true;
            }

            // User must be part of the project team
            return $project->team()->whereKey($user->id)->exists();
        });

        Gate::define('viewProjectTeam', function (User $user, Project $project): bool {
            if (strtolower((string) $user->role) === 'admin') {
                return true;
            }

            return $project->team()->whereKey($user->id)->exists();
        });

        Gate::define('manageProjectTeam', function (User $user, Project $project): bool {
            if (strtolower((string) $user->role) === 'admin') {
                return true;
            }

            if (strtolower((string) $user->role) === 'project_manager') {
                return true;
            }

            $projectRole = $project->projectUserRoles()
                ->where('user_id', $user->id)
                ->value('role');

            if (! is_string($projectRole)) {
                return false;
            }

            $normalizedRole = strtolower($projectRole);

            return str_contains($normalizedRole, 'manager') || str_contains($normalizedRole, 'lead');
        });
    }
}
