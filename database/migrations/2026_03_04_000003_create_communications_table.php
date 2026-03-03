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
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->text('last_message')->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chat_room_id');
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_rooms');
    }
};
