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
        Schema::create('project_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->uuid('generated_by');
            $table->text('prompt');
            $table->string('model')->nullable()->default('gpt-image-1');
            $table->string('quality')->nullable()->default('medium');
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->string('mime_type')->default('image/png');
            $table->bigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_images');
    }
};
