<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('paid')->default(true);
            $t->unsignedSmallInteger('annual_days')->default(0);
            $t->unsignedSmallInteger('max_consecutive_days')->nullable();
            $t->boolean('requires_document')->default(false);
            $t->unsignedSmallInteger('carry_forward_max')->default(0);
            $t->string('accrual', 30)->default('annual'); // annual|monthly|none
            $t->json('eligibility')->nullable(); // e.g. {"gender":"female","min_service_months":6}
            $t->json('approval_chain')->nullable(); // e.g. ["manager","hr"]
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $t->unsignedSmallInteger('year');
            $t->decimal('entitled', 6, 1)->default(0);
            $t->decimal('carried_forward', 6, 1)->default(0);
            $t->decimal('used', 6, 1)->default(0);
            $t->decimal('pending', 6, 1)->default(0);
            $t->timestamps();
            $t->unique(['employee_id', 'leave_type_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained('leave_types');
            $t->date('start_date');
            $t->date('end_date');
            $t->decimal('days', 6, 1);
            $t->boolean('half_day')->default(false);
            $t->text('reason')->nullable();
            $t->foreignId('attachment_file_id')->nullable()->constrained('managed_files')->nullOnDelete();
            $t->string('status', 20)->default('pending'); // pending|approved|rejected|cancelled
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'status']);
            $t->index(['start_date', 'end_date']);
        });

        Schema::create('leave_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('leave_balance_id')->constrained('leave_balances')->cascadeOnDelete();
            $t->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->nullOnDelete();
            $t->string('type', 30); // accrual|carry_forward|debit|credit|reversal
            $t->decimal('days', 6, 1);
            $t->text('note')->nullable();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['leave_balance_id']);
        });

        // Generic configurable HR approvals (leave, overtime, corrections, requisitions)
        Schema::create('hr_approvals', function (Blueprint $t) {
            $t->id();
            $t->morphs('approvable'); // approvable_type + approvable_id
            $t->unsignedTinyInteger('step');
            $t->string('approver_role', 40); // manager|hr_officer|hr_manager|branch_manager|executive|finance
            $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('pending'); // pending|approved|rejected|skipped|cancelled
            $t->text('note')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'approver_role']);
        });

        Schema::create('overtime_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->date('work_date');
            $t->time('planned_start')->nullable();
            $t->time('planned_end')->nullable();
            $t->decimal('planned_hours', 5, 2);
            $t->decimal('actual_hours', 5, 2)->nullable();
            $t->text('reason');
            $t->string('status', 20)->default('pending'); // pending|approved|rejected|verified|cancelled
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
        Schema::dropIfExists('hr_approvals');
        Schema::dropIfExists('leave_transactions');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
