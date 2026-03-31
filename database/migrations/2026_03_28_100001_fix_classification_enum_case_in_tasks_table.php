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
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['category', 'subCategory', 'finishingType']);
            $table->enum('category', ['structural', 'architectural'])->default('structural');
            $table->enum('subCategory', ['masonry', 'plumbing', 'electrical', 'finishing'])->nullable();
            $table->enum('finishingType', ['ceiling', 'painting', 'tiles', 'fixtures', 'facade', 'roofing'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['category', 'subCategory', 'finishingType']);
            $table->enum('category', ['Structural', 'Architectural'])->default('Structural');
            $table->enum('subCategory', ['Masonry', 'Plumbing', 'Electrical', 'Finishing'])->nullable();
            $table->enum('finishingType', ['Ceiling', 'Painting', 'Tiles', 'Fixtures', 'Facade', 'Roofing'])->nullable();
        });
    }
};
