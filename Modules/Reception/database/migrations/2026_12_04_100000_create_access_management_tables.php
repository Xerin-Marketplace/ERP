<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configurable approval workflows, versioned so in-flight requests
        // keep the stage set they were routed with.
        Schema::create('access_workflows', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('request_type', 30); // new_employee|existing_employee|external|*
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->unsignedInteger('current_version')->default(1);
            $t->boolean('is_active')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['request_type', 'is_active']);
        });

        Schema::create('access_workflow_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('workflow_id')->constrained('access_workflows')->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->string('status', 20)->default('published'); // draft|published|retired
            $t->date('effective_from')->nullable();
            $t->timestamps();
            $t->unique(['workflow_id', 'version']);
        });

        Schema::create('access_workflow_stages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('version_id')->constrained('access_workflow_versions')->cascadeOnDelete();
            $t->unsignedTinyInteger('sequence');
            $t->string('name');                        // e.g. "Department Manager Review"
            $t->string('approver_role', 60);           // role name required to decide
            $t->string('kind', 20)->default('sequential'); // sequential|parallel
            $t->unsignedSmallInteger('sla_hours')->nullable();
            $t->boolean('required')->default(true);
            $t->json('conditions')->nullable();        // e.g. {"request_type":"external"}
            $t->timestamps();
            $t->index(['version_id', 'sequence']);
        });

        Schema::create('access_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();     // XER-ACC-YYYY-######
            $t->string('type', 30);                    // new_employee|existing_employee|external
            $t->string('status', 30)->default('submitted');
            // submitted|reception_review|info_required|verification|in_approval|
            // approved|provisioning|provisioned|activated|rejected|cancelled|expired
            $t->string('full_name');
            $t->string('email')->index();
            $t->string('phone', 30)->nullable();
            $t->string('organization')->nullable();    // external sponsor org / company name
            $t->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->string('job_title')->nullable();
            $t->string('employment_type', 40)->nullable();
            $t->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete(); // existing
            $t->foreignId('sponsor_id')->nullable()->constrained('users')->nullOnDelete();      // external sponsor
            $t->date('start_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->json('requested_modules')->nullable(); // business module names
            $t->json('requested_changes')->nullable(); // existing-employee change spec
            $t->text('justification')->nullable();
            $t->string('tracking_token', 64)->unique(); // public secure tracking
            $t->foreignId('workflow_version_id')->nullable()->constrained('access_workflow_versions')->nullOnDelete();
            $t->unsignedTinyInteger('current_stage_seq')->default(0);
            $t->string('priority', 10)->default('normal'); // low|normal|high|urgent
            $t->foreignId('assigned_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('provisioned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('decision_reason')->nullable();
            $t->timestamp('submitted_at')->useCurrent();
            $t->timestamp('decided_at')->nullable();
            $t->timestamp('provisioned_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'type']);
            $t->index(['branch_id', 'status']);
        });

        Schema::create('access_request_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->constrained('access_requests')->cascadeOnDelete();
            $t->foreignId('file_id')->constrained('managed_files')->cascadeOnDelete();
            $t->string('category', 40)->default('supporting');
            $t->timestamps();
        });

        Schema::create('access_verifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->constrained('access_requests')->cascadeOnDelete();
            $t->string('type', 40);   // email|phone|employee_lookup|manager_confirm|sponsor_confirm|document_review
            $t->string('status', 30)->default('not_started'); // not_started|pending|verified|failed|info_required|not_applicable
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('evidence')->nullable();
            $t->text('notes')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
            $t->unique(['request_id', 'type']);
        });

        // One task per stage per request — the approval unit of work.
        Schema::create('access_approval_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->constrained('access_requests')->cascadeOnDelete();
            $t->foreignId('stage_id')->constrained('access_workflow_stages');
            $t->unsignedTinyInteger('sequence');
            $t->string('approver_role', 60);
            $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('pending'); // pending|approved|rejected|changes_requested|skipped|delegated
            $t->text('comment')->nullable();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->timestamp('due_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'approver_role']);
            $t->index(['assignee_id', 'status']);
        });

        // Append-only history — every decision & state change.
        Schema::create('access_request_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->constrained('access_requests')->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete(); // null = applicant/system
            $t->string('event', 60);
            $t->string('from_status', 30)->nullable();
            $t->string('to_status', 30)->nullable();
            $t->json('payload')->nullable();      // old/new values, notes
            $t->boolean('internal')->default(false); // internal notes never public
            $t->ipAddress('ip')->nullable();
            $t->timestamps();
            $t->index(['request_id', 'created_at']);
        });

        Schema::create('external_user_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('access_request_id')->nullable()->constrained('access_requests')->nullOnDelete();
            $t->string('category', 40); // agent|broker|contractor|consultant|partner
            $t->string('organization');
            $t->foreignId('sponsor_id')->constrained('users');
            $t->date('expires_on');
            $t->timestamp('sponsor_review_at')->nullable();
            $t->timestamps();
            $t->index(['expires_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_user_profiles');
        Schema::dropIfExists('access_request_history');
        Schema::dropIfExists('access_approval_tasks');
        Schema::dropIfExists('access_verifications');
        Schema::dropIfExists('access_request_documents');
        Schema::dropIfExists('access_requests');
        Schema::dropIfExists('access_workflow_stages');
        Schema::dropIfExists('access_workflow_versions');
        Schema::dropIfExists('access_workflows');
    }
};
