<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_tasks', function (Blueprint $t) {
            $t->id();
            $t->string('task_number', 40)->unique(); // XERIN-TSK-YYYY-######
            $t->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $t->foreignId('parent_task_id')->nullable()->constrained('ticket_tasks')->nullOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->foreignId('assignee_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->unsignedBigInteger('team_id')->nullable();
            $t->string('priority', 20)->default('normal');
            $t->string('status', 20)->default('open')->index(); // open|assigned|in_progress|blocked|pending_review|completed|cancelled
            $t->date('start_date')->nullable();
            $t->date('due_date')->nullable();
            $t->unsignedInteger('estimated_minutes')->nullable();
            $t->unsignedInteger('actual_minutes')->default(0);
            $t->unsignedTinyInteger('progress')->default(0);
            $t->foreignId('created_by')->constrained('users');
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('ticket_task_dependencies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained('ticket_tasks')->cascadeOnDelete();
            $t->foreignId('depends_on_id')->constrained('ticket_tasks')->cascadeOnDelete();
            $t->unique(['task_id', 'depends_on_id']);
        });

        Schema::create('ticket_task_checklists', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained('ticket_tasks')->cascadeOnDelete();
            $t->string('title');
            $t->boolean('done')->default(false);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('ticket_task_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained('ticket_tasks')->cascadeOnDelete();
            $t->foreignId('author_id')->constrained('users');
            $t->text('body');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_task_comments');
        Schema::dropIfExists('ticket_task_checklists');
        Schema::dropIfExists('ticket_task_dependencies');
        Schema::dropIfExists('ticket_tasks');
    }
};
