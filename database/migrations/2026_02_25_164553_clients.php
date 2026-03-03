<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('industry');
            $table->string('contact_person');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('location');
            $table->integer('active_projects')->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->enum('status', ['active', 'review', 'completed', 'on-hold'])->default('active');
            $table->foreignUuid('account_owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
