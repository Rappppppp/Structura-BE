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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_id')->unique();
            $table->uuid('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['paid', 'pending', 'overdue'])->default('pending');
            $table->dateTime('due_date');
            $table->dateTime('paid_at')->nullable();
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
