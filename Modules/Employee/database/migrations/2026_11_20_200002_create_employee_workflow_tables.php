<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_transfer_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('type', 40); // branch|department|company|position|manager|temporary
            $t->unsignedBigInteger('from_company_id')->nullable();
            $t->unsignedBigInteger('from_branch_id')->nullable();
            $t->unsignedBigInteger('from_department_id')->nullable();
            $t->unsignedBigInteger('from_position_id')->nullable();
            $t->unsignedBigInteger('from_manager_employee_id')->nullable();
            $t->unsignedBigInteger('to_company_id')->nullable();
            $t->unsignedBigInteger('to_branch_id')->nullable();
            $t->unsignedBigInteger('to_department_id')->nullable();
            $t->unsignedBigInteger('to_position_id')->nullable();
            $t->unsignedBigInteger('to_manager_employee_id')->nullable();
            $t->text('reason')->nullable();
            $t->date('effective_date')->nullable();
            $t->foreignId('requested_by')->constrained('users');
            $t->string('status', 20)->default('pending')->index(); // pending|approved|rejected|cancelled
            $t->foreignId('reviewed_by')->nullable()->constrained('users');
            $t->text('review_note')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('employee_change_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('type', 40); // promotion|demotion|position_change|grade_change|type_change|confirmation|contract_renewal|salary_reference
            $t->json('previous_values')->nullable();
            $t->json('new_values')->nullable();
            $t->text('reason')->nullable();
            $t->date('effective_date')->nullable();
            $t->foreignId('initiated_by')->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->string('status', 20)->default('pending')->index(); // pending|approved|rejected
            $t->text('review_note')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('employee_onboarding_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $t->string('status', 20)->default('in_progress'); // in_progress|completed
            $t->foreignId('started_by')->nullable()->constrained('users');
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('employee_onboarding_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('record_id')->constrained('employee_onboarding_records')->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_required')->default(true);
            $t->string('status', 20)->default('pending'); // pending|in_progress|completed|skipped
            $t->foreignId('assigned_to')->nullable()->constrained('users');
            $t->foreignId('completed_by')->nullable()->constrained('users');
            $t->timestamp('completed_at')->nullable();
            $t->date('due_date')->nullable();
            $t->timestamps();
        });

        Schema::create('employee_offboarding_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $t->string('reason', 40); // resignation|termination|retirement|contract_expiry|death|other
            $t->date('notice_date')->nullable();
            $t->date('separation_date');
            $t->string('status', 20)->default('pending')->index(); // pending|approved|completed|cancelled
            $t->foreignId('requested_by')->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('approved_at')->nullable();
            $t->json('checklist')->nullable();
            $t->text('handover_notes')->nullable();
            $t->timestamp('access_revoked_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_offboarding_records');
        Schema::dropIfExists('employee_onboarding_tasks');
        Schema::dropIfExists('employee_onboarding_records');
        Schema::dropIfExists('employee_change_requests');
        Schema::dropIfExists('employee_transfer_requests');
    }
};
