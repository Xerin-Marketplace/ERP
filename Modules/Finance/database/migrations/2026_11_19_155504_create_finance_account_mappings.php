<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Default GL accounts per company: ar, ap, cash, bank, tax_payable,
        // retained_earnings, depreciation_expense, accumulated_depreciation, ...
        Schema::create('account_mappings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $t->string('key', 60); // accounts_receivable|accounts_payable|cash|tax_payable|...
            $t->foreignId('account_id')->constrained('chart_of_accounts');
            $t->timestamps();
            $t->unique(['company_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
    }
};
