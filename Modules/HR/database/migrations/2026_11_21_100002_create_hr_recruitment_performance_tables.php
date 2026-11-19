<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_requisitions', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->string('title');
            $t->foreignId('company_id')->constrained('companies');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->unsignedSmallInteger('positions')->default(1);
            $t->string('employment_type', 30)->default('permanent');
            $t->text('justification')->nullable();
            $t->string('status', 20)->default('pending'); // pending|approved|rejected|closed
            $t->foreignId('requested_by')->constrained('users');
            $t->timestamps();
        });

        Schema::create('job_vacancies', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('requisition_id')->nullable()->constrained('recruitment_requisitions')->nullOnDelete();
            $t->string('title');
            $t->text('description');
            $t->text('requirements')->nullable();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->string('location')->nullable();
            $t->string('employment_type', 30)->default('permanent');
            $t->unsignedSmallInteger('positions')->default(1);
            $t->date('published_at')->nullable();
            $t->date('closes_at')->nullable();
            $t->string('status', 20)->default('draft'); // draft|published|closed|filled
            $t->timestamps();
        });

        Schema::create('recruitment_applications', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $t->string('full_name');
            $t->string('email');
            $t->string('phone', 30)->nullable();
            $t->foreignId('cv_file_id')->nullable()->constrained('managed_files')->nullOnDelete();
            $t->text('cover_note')->nullable();
            $t->string('stage', 30)->default('applied'); // applied|screening|shortlisted|interview|selected|offer|hired|rejected|withdrawn
            $t->text('stage_note')->nullable();
            $t->timestamps();
            $t->index(['vacancy_id', 'stage']);
        });

        Schema::create('interview_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('recruitment_applications')->cascadeOnDelete();
            $t->timestamp('scheduled_at');
            $t->string('mode', 20)->default('in_person'); // in_person|phone|video
            $t->string('location')->nullable();
            $t->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedTinyInteger('score')->nullable(); // 1-10
            $t->text('assessment')->nullable();
            $t->string('outcome', 20)->nullable(); // pass|fail|pending
            $t->timestamps();
        });

        Schema::create('performance_cycles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->date('starts_at');
            $t->date('ends_at');
            $t->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $t->string('status', 20)->default('active'); // active|closed
            $t->timestamps();
        });

        Schema::create('performance_objectives', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cycle_id')->constrained('performance_cycles')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->decimal('weight', 5, 2)->default(0); // percentage
            $t->string('status', 20)->default('draft'); // draft|agreed|in_progress|completed|cancelled
            $t->timestamps();
            $t->index(['cycle_id', 'employee_id']);
        });

        Schema::create('performance_reviews', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('cycle_id')->constrained('performance_cycles')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('kind', 20)->default('manager'); // self|manager|peer
            $t->unsignedTinyInteger('rating')->nullable(); // 1-5
            $t->text('summary')->nullable();
            $t->text('improvement_plan')->nullable();
            $t->string('status', 20)->default('draft'); // draft|submitted|acknowledged
            $t->timestamps();
            $t->unique(['cycle_id', 'employee_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('performance_objectives');
        Schema::dropIfExists('performance_cycles');
        Schema::dropIfExists('interview_records');
        Schema::dropIfExists('recruitment_applications');
        Schema::dropIfExists('job_vacancies');
        Schema::dropIfExists('recruitment_requisitions');
    }
};
