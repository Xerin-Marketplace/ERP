<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('employee_number', 40)->unique();
            $t->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $t->unsignedBigInteger('registration_application_id')->nullable();
            $t->string('status', 20)->default('onboarding')->index(); // onboarding|active|suspended|separated
            $t->string('employment_type', 30)->default('permanent');
            $t->string('employee_category', 50)->nullable();
            $t->date('start_date')->nullable();
            $t->date('confirmation_date')->nullable();
            $t->date('contract_end_date')->nullable();
            $t->date('separation_date')->nullable();
            $t->string('work_location')->nullable();
            $t->string('official_email')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('employee_personal_details', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $t->string('first_name', 100);
            $t->string('middle_name', 100)->nullable();
            $t->string('last_name', 100);
            $t->string('preferred_name', 100)->nullable();
            $t->unsignedBigInteger('photo_file_id')->nullable();
            $t->string('gender', 20)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('nationality', 100)->nullable();
            $t->string('national_id', 60)->nullable();
            $t->string('personal_email')->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('alt_phone', 30)->nullable();
            $t->text('residential_address')->nullable();
            $t->timestamps();
        });

        Schema::create('employee_emergency_contacts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('name');
            $t->string('relationship', 60)->nullable();
            $t->string('phone', 30);
            $t->string('alt_phone', 30)->nullable();
            $t->unsignedTinyInteger('priority')->default(1);
            $t->timestamps();
        });

        Schema::create('employee_document_categories', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('name');
            $t->boolean('is_confidential')->default(false);
            $t->boolean('requires_expiry')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('employee_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('category_id')->constrained('employee_document_categories');
            $t->unsignedBigInteger('managed_file_id');
            $t->string('title');
            $t->date('expires_at')->nullable();
            $t->unsignedSmallInteger('version')->default(1);
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['employee_id', 'category_id']);
        });

        Schema::create('employee_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('type', 60)->index();
            $t->string('description');
            $t->json('metadata')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_events');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employee_document_categories');
        Schema::dropIfExists('employee_emergency_contacts');
        Schema::dropIfExists('employee_personal_details');
        Schema::dropIfExists('employees');
    }
};
