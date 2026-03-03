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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->decimal('budget', 15, 2);
            $table->decimal('progress', 5, 2)->default(0);
            $table->enum('status', ['active', 'review', 'completed', 'on-hold'])->default('active');
            $table->dateTime('deadline_at');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
