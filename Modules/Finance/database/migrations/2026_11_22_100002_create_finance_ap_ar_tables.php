<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30);
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('tin', 40)->nullable(); // tax identification
            $t->text('address')->nullable();
            $t->unsignedSmallInteger('payment_terms_days')->default(30);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['company_id', 'code']);
        });

        Schema::create('supplier_invoices', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->foreignId('supplier_id')->constrained('suppliers');
            $t->string('supplier_invoice_no')->nullable();
            $t->date('invoice_date');
            $t->date('due_date')->nullable();
            $t->string('currency', 3)->default('TZS');
            $t->decimal('subtotal', 18, 2)->default(0);
            $t->decimal('tax_amount', 18, 2)->default(0);
            $t->decimal('total', 18, 2)->default(0);
            $t->decimal('paid_amount', 18, 2)->default(0);
            $t->string('status', 20)->default('draft'); // draft|submitted|approved|posted|partially_paid|paid|rejected|cancelled
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->index(['company_id', 'status']);
            $t->index(['supplier_id', 'due_date']);
            $t->unique(['supplier_id', 'supplier_invoice_no']);
        });

        Schema::create('supplier_invoice_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained('supplier_invoices')->cascadeOnDelete();
            $t->string('description');
            $t->foreignId('account_id')->constrained('chart_of_accounts');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->decimal('quantity', 12, 2)->default(1);
            $t->decimal('unit_price', 18, 2);
            $t->decimal('amount', 18, 2);
            $t->timestamps();
        });

        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30);
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('tin', 40)->nullable();
            $t->text('address')->nullable();
            $t->decimal('credit_limit', 18, 2)->nullable();
            $t->unsignedSmallInteger('payment_terms_days')->default(30);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['company_id', 'code']);
        });

        Schema::create('customer_invoices', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained('customers');
            $t->string('type', 20)->default('invoice'); // invoice|proforma|credit_note|debit_note
            $t->date('invoice_date');
            $t->date('due_date')->nullable();
            $t->string('currency', 3)->default('TZS');
            $t->decimal('subtotal', 18, 2)->default(0);
            $t->decimal('tax_amount', 18, 2)->default(0);
            $t->decimal('total', 18, 2)->default(0);
            $t->decimal('received_amount', 18, 2)->default(0);
            $t->string('status', 20)->default('draft');
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->index(['company_id', 'status']);
            $t->index(['customer_id', 'due_date']);
        });

        Schema::create('customer_invoice_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained('customer_invoices')->cascadeOnDelete();
            $t->string('description');
            $t->foreignId('account_id')->constrained('chart_of_accounts');
            $t->foreignId('tax_rate_id')->nullable();
            $t->decimal('quantity', 12, 2)->default(1);
            $t->decimal('unit_price', 18, 2);
            $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('amount', 18, 2);
            $t->timestamps();
        });

        Schema::create('payment_records', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('direction', 10); // in|out
            $t->string('method', 30); // bank_transfer|cash|cheque|mobile_money|card|other
            $t->foreignId('bank_account_id')->nullable();
            $t->date('payment_date');
            $t->decimal('amount', 18, 2);
            $t->string('payer_payee')->nullable();
            $t->string('external_ref')->nullable();
            $t->string('status', 20)->default('recorded'); // recorded|allocated|reversed
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->index(['company_id', 'direction', 'status']);
        });

        Schema::create('payment_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_id')->constrained('payment_records')->cascadeOnDelete();
            $t->string('document_type', 30); // supplier_invoice|customer_invoice|expense_claim
            $t->unsignedBigInteger('document_id');
            $t->decimal('amount', 18, 2);
            $t->timestamps();
            $t->index(['document_type', 'document_id']);
        });

        Schema::create('expense_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('code', 30);
            $t->string('name');
            $t->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $t->decimal('approval_threshold', 18, 2)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['company_id', 'code']);
        });

        Schema::create('expense_claims', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('category_id')->constrained('expense_categories');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->date('expense_date');
            $t->text('description');
            $t->decimal('amount', 18, 2);
            $t->decimal('tax_amount', 18, 2)->default(0);
            $t->foreignId('receipt_file_id')->nullable()->constrained('managed_files')->nullOnDelete();
            $t->string('status', 20)->default('draft'); // draft|submitted|manager_review|finance_review|approved|posted|paid|rejected
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_claims');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payment_records');
        Schema::dropIfExists('customer_invoice_lines');
        Schema::dropIfExists('customer_invoices');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('supplier_invoice_lines');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('suppliers');
    }
};
