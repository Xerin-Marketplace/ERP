<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('reference', 30)->unique(); // XERIN-REG-YYYY-000001

            // Personal information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 30);
            $table->string('alt_phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('residential_address')->nullable();
            $table->string('photo_path')->nullable();

            // Employment request (applicant's *request* — not authoritative)
            $table->string('employee_number', 50)->nullable();
            $table->string('employment_type', 50)->nullable();
            $table->string('job_title')->nullable();
            $table->foreignId('requested_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('requested_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('requested_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->date('employment_start_date')->nullable();
            $table->text('registration_reason')->nullable();

            // Workflow
            $table->string('status', 30)->default('submitted')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_reason')->nullable();
            $table->text('applicant_message')->nullable(); // visible to applicant (info requests)
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('provisioned_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['email', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_applications');
    }
};
