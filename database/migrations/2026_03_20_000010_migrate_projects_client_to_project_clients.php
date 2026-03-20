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
        // Migrate existing client_id data to project_clients table
        $projects = DB::table('projects')->whereNotNull('client_id')->get();
        foreach ($projects as $project) {
            DB::table('project_clients')->insert([
                'project_id' => $project->id,
                'client_id' => $project->client_id,
                'created_at' => $project->updated_at,
                'updated_at' => $project->updated_at,
            ]);
        }

        // Now drop the foreign key and column from projects table
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->onDelete('set null');
        });

        // Migrate data back from project_clients
        $projectClients = DB::table('project_clients')->get();
        foreach ($projectClients as $pc) {
            DB::table('projects')
                ->where('id', $pc->project_id)
                ->update(['client_id' => $pc->client_id]);
        }
    }
};
