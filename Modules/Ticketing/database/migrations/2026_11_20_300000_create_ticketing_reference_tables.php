<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->unsignedBigInteger('department_id')->nullable(); // owning department
            $t->json('approval_chain')->nullable(); // e.g. ["manager","it"]
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('ticket_categories', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('name');
            $t->foreignId('parent_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $t->unsignedBigInteger('default_team_id')->nullable();
            $t->unsignedBigInteger('default_department_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('ticket_teams', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedBigInteger('leader_employee_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('ticket_team_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('team_id')->constrained('ticket_teams')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['team_id', 'employee_id']);
        });

        Schema::create('service_catalog_items', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->foreignId('ticket_type_id')->nullable()->constrained('ticket_types')->nullOnDelete();
            $t->foreignId('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $t->unsignedBigInteger('owning_department_id')->nullable();
            $t->unsignedBigInteger('default_team_id')->nullable();
            $t->string('priority', 20)->default('normal');
            $t->json('required_fields')->nullable();
            $t->boolean('requires_attachment')->default(false);
            $t->boolean('requires_approval')->default(false);
            $t->json('approval_chain')->nullable();
            $t->unsignedInteger('expected_hours')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('service_form_fields', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_id')->constrained('service_catalog_items')->cascadeOnDelete();
            $t->string('key', 60);
            $t->string('label');
            $t->string('type', 20)->default('text'); // text|textarea|select|date|number
            $t->json('options')->nullable();
            $t->boolean('required')->default(false);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('ticket_sla_policies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('priority', 20)->nullable(); // applies to this priority
            $t->foreignId('ticket_type_id')->nullable()->constrained('ticket_types')->nullOnDelete();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedInteger('response_minutes')->default(60);
            $t->unsignedInteger('resolution_minutes')->default(480);
            $t->unsignedTinyInteger('business_hours_start')->default(8);
            $t->unsignedTinyInteger('business_hours_end')->default(17);
            $t->json('business_days')->nullable(); // [1..7] ISO weekdays; null = every day
            $t->boolean('pauses_on_pending')->default(true);
            $t->unsignedSmallInteger('warn_percent')->default(80); // warn at % of target
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('ticket_escalation_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('trigger', 40); // sla_breach|unassigned|overdue|critical|warning
            $t->string('priority', 20)->nullable();
            $t->unsignedInteger('after_minutes')->nullable();
            $t->unsignedBigInteger('to_team_id')->nullable();
            $t->foreignId('to_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('notify_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('new_priority', 20)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('ticket_automation_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('event', 40); // created|updated|scheduled
            $t->json('conditions')->nullable(); // {priority, category_id, service_id, type_id}
            $t->string('action', 60); // assign_team|assign_category_default|notify_managers|set_priority|auto_close
            $t->json('action_params')->nullable(); // {team_id, user_id, priority, after_hours}
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('max_runs_per_ticket')->default(3); // loop guard
            $t->timestamps();
        });

        Schema::create('knowledge_categories', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('knowledge_articles', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique(); // KB-0001
            $t->foreignId('category_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $t->string('title');
            $t->longText('body');
            $t->string('status', 20)->default('draft'); // draft|published
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('helpful_count')->default(0);
            $t->unsignedInteger('view_count')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_categories');
        Schema::dropIfExists('ticket_automation_rules');
        Schema::dropIfExists('ticket_escalation_rules');
        Schema::dropIfExists('ticket_sla_policies');
        Schema::dropIfExists('service_form_fields');
        Schema::dropIfExists('service_catalog_items');
        Schema::dropIfExists('ticket_team_members');
        Schema::dropIfExists('ticket_teams');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('ticket_types');
    }
};
