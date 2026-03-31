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
        // Modify the status enum to include 'cancelled'
        Schema::table('projects', function (Blueprint $table) {
            // For MySQL, we need to drop and recreate the enum
            $table->dropColumn('status');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('status', ['active', 'review', 'completed', 'on-hold', 'cancelled'])
                ->default('active')
                ->after('progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('status', ['active', 'review', 'completed', 'on-hold'])
                ->default('active')
                ->after('progress');
        });
    }
};
