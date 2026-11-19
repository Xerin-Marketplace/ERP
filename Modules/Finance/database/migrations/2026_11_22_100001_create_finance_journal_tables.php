<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->foreignId('period_id')->constrained('financial_periods');
            $t->string('type', 30)->default('general'); // general|sales|purchase|cash_receipt|cash_payment|bank|adjustment|opening|depreciation|tax|reversal
            $t->date('journal_date');
            $t->string('currency', 3)->default('TZS');
            $t->decimal('exchange_rate', 18, 8)->default(1);
            $t->text('memo')->nullable();
            $t->string('status', 20)->default('draft'); // draft|submitted|approved|posted|rejected|reversed
            $t->string('source_type')->nullable(); // e.g. supplier_invoice
            $t->unsignedBigInteger('source_id')->nullable();
            $t->foreignId('reversal_of_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->foreignId('reversed_by_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('posted_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamps();
            $t->index(['company_id', 'status']);
            $t->index(['period_id', 'journal_date']);
            $t->index(['source_type', 'source_id']);
        });

        Schema::create('journal_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $t->foreignId('account_id')->constrained('chart_of_accounts');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->string('description')->nullable();
            $t->decimal('debit', 18, 2)->default(0);
            $t->decimal('credit', 18, 2)->default(0);
            $t->timestamps();
            $t->index(['journal_id']);
            $t->index(['account_id']);
        });

        // Append-only ledger — written once at posting, never updated/deleted
        Schema::create('general_ledger_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies');
            $t->foreignId('period_id')->constrained('financial_periods');
            $t->foreignId('journal_id')->constrained('journals');
            $t->foreignId('journal_line_id')->constrained('journal_lines');
            $t->foreignId('account_id')->constrained('chart_of_accounts');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->date('entry_date');
            $t->decimal('debit', 18, 2)->default(0);
            $t->decimal('credit', 18, 2)->default(0);
            $t->string('reference', 40); // journal reference
            $t->string('description')->nullable();
            $t->timestamps();
            $t->index(['account_id', 'entry_date']);
            $t->index(['company_id', 'period_id']);
            $t->unique('journal_line_id'); // a line posts exactly once
        });

        // Generic finance approvals — polymorphic like hr_approvals
        Schema::create('finance_approvals', function (Blueprint $t) {
            $t->id();
            $t->morphs('approvable');
            $t->unsignedTinyInteger('step');
            $t->string('approver_role', 40);
            $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('pending');
            $t->text('note')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'approver_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_approvals');
        Schema::dropIfExists('general_ledger_entries');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
    }
};
