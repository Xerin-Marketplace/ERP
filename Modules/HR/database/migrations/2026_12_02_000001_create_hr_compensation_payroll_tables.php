<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ---------- Personal info extension ---------- */
        Schema::table('employee_personal_details', function (Blueprint $t) {
            $t->string('marital_status', 30)->nullable()->after('nationality');
            $t->text('passport_number')->nullable()->after('national_id');
            $t->date('passport_expiry')->nullable();
            $t->string('work_permit_number', 60)->nullable();
            $t->date('work_permit_expiry')->nullable();
            $t->string('country', 100)->nullable()->after('residential_address');
            $t->string('region', 100)->nullable();
            $t->string('city', 100)->nullable();
            $t->string('postal_address')->nullable();
            $t->string('next_of_kin_name')->nullable();
            $t->string('next_of_kin_phone', 30)->nullable();
            $t->string('next_of_kin_relationship', 60)->nullable();
        });

        /* ---------- Bank & Payment Details (encrypted at rest) ---------- */
        Schema::create('employee_bank_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('account_holder_name');
            $t->string('bank_name');
            $t->string('bank_branch')->nullable();
            $t->text('account_number');      // encrypted cast
            $t->text('iban')->nullable();    // encrypted cast; not required for TZ
            $t->string('swift_bic', 20)->nullable();
            $t->string('currency', 3)->default('TZS');
            $t->string('country', 2)->nullable();
            $t->string('payment_method', 30)->default('bank_transfer'); // bank_transfer|mobile_money|cash|cheque
            $t->string('payment_frequency', 20)->default('monthly');
            $t->string('verification_status', 20)->default('pending'); // pending|verified|rejected
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->boolean('is_primary')->default(true);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['employee_id', 'is_primary']);
        });

        /* ---------- Compensation (salary structure + revision history) ---------- */
        Schema::create('employee_compensations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->decimal('basic_salary', 14, 2);
            $t->decimal('housing_allowance', 12, 2)->default(0);
            $t->decimal('transport_allowance', 12, 2)->default(0);
            $t->decimal('medical_allowance', 12, 2)->default(0);
            $t->decimal('communication_allowance', 12, 2)->default(0);
            $t->decimal('meal_allowance', 12, 2)->default(0);
            $t->decimal('other_allowances', 12, 2)->default(0);
            $t->decimal('overtime_rate', 10, 2)->nullable(); // per hour
            $t->decimal('pension_deduction', 12, 2)->default(0);
            $t->decimal('insurance_deduction', 12, 2)->default(0);
            $t->decimal('tax_deduction', 12, 2)->default(0);
            $t->decimal('other_deductions', 12, 2)->default(0);
            $t->string('currency', 3)->default('TZS');
            $t->string('pay_frequency', 20)->default('monthly');
            $t->string('status', 20)->default('pending'); // pending|approved|superseded
            $t->date('effective_date');
            $t->text('reason')->nullable();
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('supersedes_id')->nullable()->constrained('employee_compensations')->nullOnDelete();
            $t->timestamps();
            $t->index(['employee_id', 'status', 'effective_date']);
        });

        /* ---------- Payroll ---------- */
        Schema::create('payroll_periods', function (Blueprint $t) {
            $t->id();
            $t->string('name', 40);                    // e.g. "2026-02"
            $t->date('start_date');
            $t->date('end_date');
            $t->string('status', 20)->default('open'); // open|processing|approved|closed
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->unique(['name', 'branch_id']);
        });

        Schema::create('payroll_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('reference', 40)->unique();     // PAY-2026-02-000001
            $t->decimal('gross_amount', 14, 2);
            $t->decimal('total_deductions', 14, 2)->default(0);
            $t->decimal('net_amount', 14, 2);
            $t->string('currency', 3)->default('TZS');
            $t->string('status', 20)->default('draft'); // draft|approved|paid|reversed
            $t->json('breakdown')->nullable();          // snapshot of components used
            $t->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
            $t->unique(['payroll_period_id', 'employee_id']);
            $t->index(['status', 'payroll_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('employee_compensations');
        Schema::dropIfExists('employee_bank_accounts');
        Schema::table('employee_personal_details', function (Blueprint $t) {
            $t->dropColumn(['marital_status', 'passport_number', 'passport_expiry',
                'work_permit_number', 'work_permit_expiry', 'country', 'region',
                'city', 'postal_address', 'next_of_kin_name', 'next_of_kin_phone',
                'next_of_kin_relationship']);
        });
    }
};
