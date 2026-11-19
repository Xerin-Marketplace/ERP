<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('account_id')->constrained('chart_of_accounts'); // GL cash/bank account
            $t->string('name');
            $t->string('bank_name')->nullable();
            $t->string('account_number')->nullable(); // restricted display
            $t->string('currency', 3)->default('TZS');
            $t->decimal('opening_balance', 18, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $t->date('txn_date');
            $t->string('description');
            $t->decimal('debit', 18, 2)->default(0);  // money out of bank
            $t->decimal('credit', 18, 2)->default(0); // money into bank
            $t->string('reference')->nullable();
            $t->string('source', 20)->default('import'); // import|manual
            $t->foreignId('matched_payment_id')->nullable()->constrained('payment_records')->nullOnDelete();
            $t->timestamps();
            $t->index(['bank_account_id', 'txn_date']);
        });

        Schema::create('bank_reconciliations', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $t->date('statement_date');
            $t->decimal('statement_balance', 18, 2);
            $t->decimal('book_balance', 18, 2);
            $t->decimal('difference', 18, 2);
            $t->string('status', 20)->default('open'); // open|approved|closed
            $t->foreignId('prepared_by')->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('budgets', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->foreignId('year_id')->constrained('financial_years');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->string('name');
            $t->unsignedInteger('version')->default(1);
            $t->string('status', 20)->default('draft'); // draft|submitted|reviewed|approved|active|closed|rejected
            $t->string('control_policy', 20)->default('warn'); // warn|approval|block
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->index(['company_id', 'status']);
        });

        Schema::create('budget_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $t->foreignId('account_id')->constrained('chart_of_accounts');
            $t->decimal('amount', 18, 2);
            $t->decimal('consumed', 18, 2)->default(0);
            $t->timestamps();
            $t->unique(['budget_id', 'account_id']);
        });

        Schema::create('tax_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('code', 30);
            $t->string('name');
            $t->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $t->timestamps();
            $t->unique(['company_id', 'code']);
        });

        Schema::create('tax_rates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained('tax_categories')->cascadeOnDelete();
            $t->string('name');
            $t->decimal('rate', 8, 4); // e.g. 18.0000
            $t->boolean('inclusive')->default(false);
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('tax_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies');
            $t->foreignId('tax_rate_id')->constrained('tax_rates');
            $t->string('document_type', 40);
            $t->unsignedBigInteger('document_id');
            $t->decimal('base_amount', 18, 2);
            $t->decimal('tax_amount', 18, 2);
            $t->decimal('rate_applied', 8, 4); // snapshot
            $t->timestamps();
            $t->index(['document_type', 'document_id']);
        });

        Schema::create('asset_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('code', 30);
            $t->string('name');
            $t->foreignId('asset_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $t->foreignId('depreciation_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $t->foreignId('accumulated_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $t->unsignedSmallInteger('useful_life_months')->default(48);
            $t->string('method', 30)->default('straight_line'); // straight_line|declining_balance
            $t->decimal('declining_rate', 8, 4)->nullable();
            $t->timestamps();
            $t->unique(['company_id', 'code']);
        });

        Schema::create('fixed_assets', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->foreignId('category_id')->constrained('asset_categories');
            $t->string('name');
            $t->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->foreignId('custodian_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->date('acquired_on');
            $t->decimal('cost', 18, 2);
            $t->decimal('residual_value', 18, 2)->default(0);
            $t->decimal('accumulated_depreciation', 18, 2)->default(0);
            $t->string('status', 20)->default('active'); // active|disposed|transferred
            $t->timestamps();
        });

        Schema::create('asset_depreciation_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $t->foreignId('period_id')->constrained('financial_periods');
            $t->decimal('amount', 18, 2);
            $t->string('status', 20)->default('scheduled'); // scheduled|posted
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->timestamps();
            $t->unique(['asset_id', 'period_id']); // never twice for same period
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_schedules');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('asset_categories');
        Schema::dropIfExists('tax_transactions');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_categories');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};
