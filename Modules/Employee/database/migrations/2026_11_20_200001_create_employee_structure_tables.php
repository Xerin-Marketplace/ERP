<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_grades', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->unsignedSmallInteger('level')->default(1);
            $t->unsignedBigInteger('company_id')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('job_positions', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('title');
            $t->text('description')->nullable();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->foreignId('job_grade_id')->nullable()->constrained('job_grades')->nullOnDelete();
            $t->foreignId('reports_to_position_id')->nullable()->constrained('job_positions')->nullOnDelete();
            $t->string('category', 60)->nullable();
            $t->text('minimum_qualifications')->nullable();
            $t->text('responsibilities')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->index(['company_id', 'department_id']);
        });

        Schema::create('employee_employment_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('type', 30); // permanent|contract|temporary|probation|intern|part_time
            $t->string('contract_reference', 80)->nullable();
            $t->string('status', 20)->default('active'); // draft|active|renewed|expired|terminated
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->string('confirmation_status', 20)->default('pending'); // pending|confirmed|extended
            $t->foreignId('renewed_from_id')->nullable()->constrained('employee_employment_records')->nullOnDelete();
            $t->timestamps();
            $t->index(['employee_id', 'status']);
        });

        Schema::create('employee_reporting_relationships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('manager_employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('type', 20)->default('direct'); // direct|functional
            $t->date('effective_start')->nullable();
            $t->date('effective_end')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'effective_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_reporting_relationships');
        Schema::dropIfExists('employee_employment_records');
        Schema::dropIfExists('job_positions');
        Schema::dropIfExists('job_grades');
    }
};
