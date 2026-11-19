<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('provider')->nullable();
            $t->date('starts_at')->nullable();
            $t->date('ends_at')->nullable();
            $t->decimal('budget', 12, 2)->nullable();
            $t->unsignedSmallInteger('capacity')->nullable();
            $t->boolean('certifies')->default(false);
            $t->unsignedSmallInteger('cert_validity_months')->nullable();
            $t->string('status', 20)->default('open'); // open|closed|completed|cancelled
            $t->timestamps();
        });

        Schema::create('training_enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('program_id')->constrained('training_programs')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('status', 20)->default('enrolled'); // enrolled|attended|completed|failed|withdrawn
            $t->unsignedTinyInteger('score')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['program_id', 'employee_id']);
        });

        Schema::create('training_certifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrollment_id')->nullable()->constrained('training_enrollments')->nullOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('title');
            $t->string('issuer')->nullable();
            $t->date('issued_at')->nullable();
            $t->date('expires_at')->nullable();
            $t->foreignId('file_id')->nullable()->constrained('managed_files')->nullOnDelete();
            $t->timestamps();
            $t->index(['expires_at']);
        });

        Schema::create('disciplinary_cases', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('category', 40); // misconduct|insubordination|absenteeism|performance|harassment|other
            $t->text('incident');
            $t->date('incident_date')->nullable();
            $t->string('severity', 20)->default('minor'); // minor|moderate|major|gross
            $t->string('status', 30)->default('open'); // open|investigating|hearing|decided|appealed|closed
            $t->foreignId('opened_by')->constrained('users');
            $t->foreignId('investigator_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('employee_response')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'status']);
        });

        Schema::create('disciplinary_actions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('case_id')->constrained('disciplinary_cases')->cascadeOnDelete();
            $t->string('type', 40); // verbal_warning|written_warning|final_warning|suspension|termination|exonerated|appeal|note
            $t->text('detail')->nullable();
            $t->foreignId('file_id')->nullable()->constrained('managed_files')->nullOnDelete();
            $t->foreignId('actor_id')->constrained('users');
            $t->timestamps();
        });

        Schema::create('welfare_records', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('type', 40); // benefit|assistance|program|activity
            $t->string('title');
            $t->text('detail')->nullable();
            $t->decimal('value', 12, 2)->nullable();
            $t->date('recorded_on');
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });

        Schema::create('hr_documents', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $t->string('category', 40); // contract|letter|warning|certificate|policy|performance|recruitment|leave
            $t->string('title');
            $t->foreignId('file_id')->constrained('managed_files');
            $t->date('expires_at')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->foreignId('supersedes_id')->nullable()->constrained('hr_documents')->nullOnDelete();
            $t->boolean('confidential')->default(false);
            $t->timestamps();
            $t->index(['employee_id', 'category']);
        });

        Schema::create('hr_announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $t->date('published_at');
            $t->date('expires_at')->nullable();
            $t->foreignId('author_id')->constrained('users');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_announcements');
        Schema::dropIfExists('hr_documents');
        Schema::dropIfExists('welfare_records');
        Schema::dropIfExists('disciplinary_actions');
        Schema::dropIfExists('disciplinary_cases');
        Schema::dropIfExists('training_certifications');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_programs');
    }
};
