<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_definitions', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->time('start_time');
            $t->time('end_time');
            $t->boolean('overnight')->default(false);
            $t->unsignedSmallInteger('break_minutes')->default(60);
            $t->unsignedSmallInteger('grace_minutes')->default(15);
            $t->json('working_days')->nullable(); // [1..7] ISO weekdays; null = all
            $t->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['is_active']);
        });

        Schema::create('shift_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shift_id')->constrained('shift_definitions')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('team_id')->nullable()->constrained('ticket_teams')->nullOnDelete();
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('active'); // active|ended|cancelled
            $t->timestamps();
            $t->index(['employee_id', 'effective_from']);
        });

        Schema::create('attendance_devices', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->string('provider', 40); // biometric|rfid|access_control|api
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->foreignId('shift_id')->nullable()->constrained('shift_definitions')->nullOnDelete();
            $t->date('work_date');
            $t->timestamp('clock_in_at')->nullable();
            $t->timestamp('clock_out_at')->nullable();
            $t->unsignedSmallInteger('break_minutes')->default(0);
            $t->unsignedSmallInteger('worked_minutes')->default(0);
            $t->unsignedSmallInteger('late_minutes')->default(0);
            $t->unsignedSmallInteger('early_departure_minutes')->default(0);
            $t->unsignedSmallInteger('overtime_minutes')->default(0);
            $t->string('status', 30)->default('present'); // present|absent|late|half_day|on_leave|holiday|rest_day|pending_verification
            $t->string('verification', 20)->default('auto'); // auto|verified|corrected|disputed
            $t->string('source', 30)->default('web'); // web|mobile|device|manual|import
            $t->foreignId('device_id')->nullable()->constrained('attendance_devices')->nullOnDelete();
            $t->timestamps();
            $t->unique(['employee_id', 'work_date']);
            $t->index(['company_id', 'work_date']);
            $t->index(['status', 'work_date']);
        });

        Schema::create('attendance_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attendance_id')->constrained('attendance_records')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('type', 20); // clock_in|clock_out|break_start|break_end
            $t->timestamp('occurred_at');
            $t->string('source', 30)->default('web');
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'occurred_at']);
        });

        Schema::create('attendance_corrections', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('attendance_id')->nullable()->constrained('attendance_records')->nullOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->date('work_date');
            $t->string('type', 30); // missing_clock_in|missing_clock_out|time_adjustment|other
            $t->time('requested_clock_in')->nullable();
            $t->time('requested_clock_out')->nullable();
            $t->text('reason');
            $t->string('status', 20)->default('pending'); // pending|approved|rejected|cancelled
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'status']);
        });

        Schema::create('attendance_import_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('device_id')->nullable()->constrained('attendance_devices')->nullOnDelete();
            $t->string('provider', 40);
            $t->unsignedInteger('records_received')->default(0);
            $t->unsignedInteger('records_imported')->default(0);
            $t->unsignedInteger('duplicates_skipped')->default(0);
            $t->unsignedInteger('errors')->default(0);
            $t->json('error_detail')->nullable();
            $t->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_import_logs');
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('attendance_events');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_devices');
        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('shift_definitions');
    }
};
