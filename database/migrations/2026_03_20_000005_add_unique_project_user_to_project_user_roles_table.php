<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_user_roles', function (Blueprint $table) {
            $table->unique(['project_id', 'user_id'], 'project_user_roles_project_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('project_user_roles', function (Blueprint $table) {
            $table->dropUnique('project_user_roles_project_user_unique');
        });
    }
};
