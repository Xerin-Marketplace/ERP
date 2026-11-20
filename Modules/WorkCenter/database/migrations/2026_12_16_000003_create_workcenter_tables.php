<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_categories', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('code', 30)->unique();
            $t->boolean('active')->default(true); $t->timestamps();
        });

        Schema::create('report_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained('report_categories')->cascadeOnDelete();
            $t->string('name'); $t->text('description')->nullable();
            $t->json('sections');                       // ordered section defs [{key,title,required}]
            $t->string('theme', 30)->default('corporate');
            $t->string('default_visibility', 20)->default('department');
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->boolean('active')->default(true); $t->timestamps();
        });

        Schema::create('reports', function (Blueprint $t) {
            $t->id(); $t->string('ref', 30)->unique();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->foreignId('owner_id')->constrained('users');
            $t->foreignId('category_id')->constrained('report_categories');
            $t->foreignId('template_id')->nullable()->constrained('report_templates')->nullOnDelete();
            $t->string('title');
            $t->string('type', 10)->default('builder'); // builder|upload
            $t->date('period_start')->nullable(); $t->date('period_end')->nullable();
            $t->string('visibility', 20)->default('private'); // private|reviewers|department|branch|company|explicit
            $t->string('classification', 20)->default('internal'); // public|internal|confidential|restricted
            $t->string('status', 20)->default('draft')->index();
            $t->unsignedInteger('version_no')->default(1);
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('report_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->json('content')->nullable();            // builder: {section_key: html}
            $t->foreignId('file_id')->nullable()->constrained('managed_files')->nullOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->text('change_note')->nullable(); $t->timestamps();
            $t->unique(['report_id', 'version']);
        });

        Schema::create('report_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $t->foreignId('reviewer_id')->constrained('users');
            $t->string('decision', 20);                 // approved|rejected|returned
            $t->text('comment')->nullable(); $t->timestamp('decided_at');
            $t->timestamps();
        });

        Schema::create('document_categories', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('code', 40)->unique();
            $t->timestamps();
        });

        Schema::create('documents', function (Blueprint $t) {
            $t->id(); $t->string('ref', 30)->unique();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->foreignId('category_id')->constrained('document_categories');
            $t->foreignId('owner_id')->constrained('users');
            $t->string('title'); $t->text('description')->nullable();
            $t->json('tags')->nullable();
            $t->foreignId('file_id')->constrained('managed_files');
            $t->string('visibility', 20)->default('private');
            $t->date('expires_at')->nullable();
            $t->string('status', 20)->default('active')->index(); // active|review|approved|archived
            $t->unsignedInteger('version_no')->default(1);
            // polymorphic link: tender|contract|employee|shipment...
            $t->nullableMorphs('attachable');
            $t->timestamps();
        });

        Schema::create('document_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->foreignId('file_id')->constrained('managed_files');
            $t->text('change_note')->nullable();
            $t->foreignId('uploaded_by')->constrained('users');
            $t->timestamps(); $t->unique(['document_id', 'version']);
        });

        Schema::create('document_shares', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('permission', 10)->default('view'); // view|download
            $t->foreignId('granted_by')->constrained('users');
            $t->timestamps(); $t->unique(['document_id', 'user_id']);
        });

        Schema::create('work_tasks', function (Blueprint $t) {
            $t->id(); $t->string('ref', 30)->unique();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->string('title'); $t->text('description')->nullable();
            $t->string('priority', 10)->default('medium'); // critical|high|medium|low
            $t->string('status', 20)->default('assigned')->index();
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('assignee_id')->constrained('users');
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('requires_review')->default(true);
            $t->unsignedTinyInteger('progress')->default(0);
            $t->dateTime('starts_at')->nullable(); $t->dateTime('due_at')->nullable();
            $t->timestamp('completed_at')->nullable(); $t->timestamps();
        });

        Schema::create('task_progress_updates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained('work_tasks')->cascadeOnDelete();
            $t->unsignedTinyInteger('progress');
            $t->text('note')->nullable();
            $t->foreignId('evidence_file_id')->nullable()->constrained('managed_files')->nullOnDelete();
            $t->foreignId('by_user_id')->constrained('users');
            $t->timestamps();
        });

        Schema::create('task_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained('work_tasks')->cascadeOnDelete();
            $t->foreignId('reviewer_id')->constrained('users');
            $t->string('decision', 20); // approved|revision
            $t->text('comment')->nullable(); $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['task_reviews','task_progress_updates','work_tasks','document_shares',
            'document_versions','documents','document_categories','report_reviews',
            'report_versions','reports','report_templates','report_categories'] as $tb) {
            Schema::dropIfExists($tb);
        }
    }
};
