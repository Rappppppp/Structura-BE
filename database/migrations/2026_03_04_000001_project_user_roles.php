<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role');
            $table->string('avatar')->nullable();
            $table->integer('projects_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('project_user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user_roles');
        Schema::dropIfExists('team_members');
    }
};
