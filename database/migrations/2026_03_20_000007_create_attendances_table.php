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
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('scope'); // 'global', 'project', 'team'
            $table->uuid('scope_id')->nullable()->index(); // project_id or team_id
            $table->string('scope_name')->nullable(); // cached name for convenience
            $table->dateTime('check_in_time');
            $table->dateTime('check_out_time')->nullable();
            $table->longText('check_in_photo')->nullable(); // base64 encoded
            $table->longText('check_out_photo')->nullable(); // base64 encoded
            $table->integer('duration')->nullable(); // in minutes
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
