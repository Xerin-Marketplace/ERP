<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('base_currency', 3)->default('TZS');
            $t->unsignedTinyInteger('decimal_places')->default(2);
            $t->boolean('require_journal_approval')->default(true);
            $t->decimal('journal_approval_threshold', 15, 2)->nullable();
            $t->timestamps();
            $t->unique('company_id');
        });

        Schema::create('financial_years', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('name');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('status', 20)->default('open'); // open|closed
            $t->timestamps();
            $t->index(['company_id', 'starts_on']);
        });

        Schema::create('financial_periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('year_id')->constrained('financial_years')->cascadeOnDelete();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('name'); // e.g. "Jan 2026"
            $t->unsignedTinyInteger('sequence'); // 1..12 (+optional 13 adj)
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('status', 20)->default('open'); // open|closed|locked
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->unique(['year_id', 'sequence']);
        });

        Schema::create('exchange_rates', function (Blueprint $t) {
            $t->id();
            $t->string('from_currency', 3);
            $t->string('to_currency', 3);
            $t->decimal('rate', 18, 8);
            $t->date('effective_on');
            $t->string('source')->nullable();
            $t->timestamps();
            $t->index(['from_currency', 'to_currency', 'effective_on']);
        });

        Schema::create('chart_of_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('code', 30);
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('type', 20); // asset|liability|equity|revenue|expense
            $t->string('subtype', 40)->nullable(); // e.g. current_asset, bank, ar, ap
            $t->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $t->string('normal_balance', 10); // debit|credit
            $t->boolean('postable')->default(true);
            $t->boolean('is_active')->default(true);
            $t->string('currency', 3)->nullable(); // null = any
            $t->timestamps();
            $t->unique(['company_id', 'code']);
            $t->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('financial_periods');
        Schema::dropIfExists('financial_years');
        Schema::dropIfExists('financial_settings');
    }
};
