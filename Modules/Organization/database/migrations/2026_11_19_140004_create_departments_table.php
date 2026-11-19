<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            // Optional link to a reusable definition (same classification, many placements)
            $table->foreignId('department_definition_id')->nullable()
                  ->constrained('department_definitions')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()
                  ->constrained('departments')->nullOnDelete();
            $table->unsignedBigInteger('head_id')->nullable();      // future Employee FK
            $table->unsignedBigInteger('deputy_head_id')->nullable(); // future Employee FK
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status', 20)->default('active');
            $table->date('effective_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
