<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_user_roles', function (Blueprint $table) {
            // Add base_role and specialty_role columns
            $table->string('base_role')->default('member')->after('role'); // admin|member|viewer
            $table->string('specialty_role')->nullable()->after('base_role'); // architect|engineer|pm|bim|etc

            // Temporarily keep old 'role' for backward compatibility during migration
            // We'll migrate data from 'role' to 'base_role'/'specialty_role' in the migration logic below
        });

        // Migrate existing role data
        // Map old single role values to base_role and specialty_role
        $roles = DB::table('project_user_roles')->get();
        foreach ($roles as $role) {
            $baseRole = 'member';
            $specialtyRole = null;

            // Map old role values to new structure
            if ($role->role === 'admin') {
                $baseRole = 'admin';
            } elseif ($role->role === 'viewer') {
                $baseRole = 'viewer';
            } elseif (in_array($role->role, ['Architect', 'architect', 'Lead Architect'])) {
                $baseRole = 'member';
                $specialtyRole = 'architect';
            } elseif (in_array($role->role, ['Engineer', 'engineer', 'MEP Engineer'])) {
                $baseRole = 'member';
                $specialtyRole = 'engineer';
            } elseif (in_array($role->role, ['PM', 'pm', 'Project Manager'])) {
                $baseRole = 'member';
                $specialtyRole = 'pm';
            } elseif (in_array($role->role, ['BIM', 'bim', 'BIM Specialist'])) {
                $baseRole = 'member';
                $specialtyRole = 'bim';
            }

            DB::table('project_user_roles')
                ->where('id', $role->id)
                ->update([
                    'base_role' => $baseRole,
                    'specialty_role' => $specialtyRole,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_user_roles', function (Blueprint $table) {
            $table->dropColumn(['base_role', 'specialty_role']);
        });
    }
};
