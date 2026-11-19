<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_number', 40)->unique(); // XERIN-TKT-YYYY-######
            $t->string('subject');
            $t->text('description');
            $t->foreignId('ticket_type_id')->nullable()->constrained('ticket_types')->nullOnDelete();
            $t->foreignId('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $t->unsignedBigInteger('service_id')->nullable(); // service_catalog_items
            $t->string('priority', 20)->default('normal')->index();
            $t->string('severity', 20)->default('minor');
            $t->string('status', 20)->default('new')->index();
            $t->foreignId('requester_id')->constrained('users');
            $t->unsignedBigInteger('requester_employee_id')->nullable();
            $t->unsignedBigInteger('requester_company_id')->nullable();
            $t->unsignedBigInteger('requester_branch_id')->nullable();
            $t->unsignedBigInteger('requester_department_id')->nullable();
            $t->unsignedBigInteger('assigned_department_id')->nullable();
            $t->unsignedBigInteger('assigned_team_id')->nullable();
            $t->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('due_at')->nullable();
            $t->dateTime('resolved_at')->nullable();
            $t->dateTime('closed_at')->nullable();
            $t->dateTime('first_response_at')->nullable();
            $t->unsignedSmallInteger('reopen_count')->default(0);
            $t->json('custom_fields')->nullable();
            $t->timestamps();
            $t->index(['status', 'priority']);
            $t->index(['assigned_agent_id', 'status']);
        });

        Schema::create('ticket_sla_tracking', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->unique()->constrained('tickets')->cascadeOnDelete();
            $t->foreignId('policy_id')->constrained('ticket_sla_policies');
            $t->dateTime('response_due_at');
            $t->dateTime('response_at')->nullable();
            $t->dateTime('resolution_due_at');
            $t->dateTime('paused_at')->nullable();
            $t->unsignedInteger('paused_minutes')->default(0);
            $t->boolean('response_breached')->default(false);
            $t->boolean('resolution_breached')->default(false);
            $t->boolean('response_warned')->default(false);
            $t->boolean('resolution_warned')->default(false);
            $t->timestamps();
        });

        Schema::create('ticket_status_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $t->string('from_status', 20)->nullable();
            $t->string('to_status', 20);
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('note')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('ticket_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $t->unsignedBigInteger('team_id')->nullable();
            $t->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->foreignId('assigned_by')->constrained('users');
            $t->string('reason')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('ticket_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $t->foreignId('author_id')->constrained('users');
            $t->text('body');
            $t->boolean('is_internal')->default(false); // internal notes hidden from requester
            $t->string('kind', 20)->default('comment'); // comment|note|system
            $t->timestamps();
            $t->index(['ticket_id', 'is_internal']);
        });

        Schema::create('ticket_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $t->foreignId('comment_id')->nullable()->constrained('ticket_comments')->nullOnDelete();
            $t->unsignedBigInteger('managed_file_id');
            $t->foreignId('uploaded_by')->constrained('users');
            $t->timestamps();
        });

        Schema::create('ticket_tags', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $t->string('tag', 60);
            $t->unique(['ticket_id', 'tag']);
        });

        Schema::create('ticket_approvals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $t->unsignedSmallInteger('step')->default(1);
            $t->string('approver_role', 40); // manager|it|finance|department_head|custom
            $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('pending'); // pending|approved|rejected|skipped
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('decision_note')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->index(['approver_id', 'status']);
        });

        Schema::create('ticket_escalations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $t->foreignId('rule_id')->nullable()->constrained('ticket_escalation_rules')->nullOnDelete();
            $t->string('trigger', 40);
            $t->json('details')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('ticket_automation_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->nullable()->constrained('tickets')->cascadeOnDelete();
            $t->foreignId('rule_id')->constrained('ticket_automation_rules')->cascadeOnDelete();
            $t->string('action', 60);
            $t->string('result', 20)->default('success'); // success|skipped|failed
            $t->text('message')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['ticket_id', 'rule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_automation_logs');
        Schema::dropIfExists('ticket_escalations');
        Schema::dropIfExists('ticket_approvals');
        Schema::dropIfExists('ticket_tags');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_comments');
        Schema::dropIfExists('ticket_assignments');
        Schema::dropIfExists('ticket_status_histories');
        Schema::dropIfExists('ticket_sla_tracking');
        Schema::dropIfExists('tickets');
    }
};
