<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Release approvals — hard block: no delivery without Approved + release code.
        Schema::create('release_approvals', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique(); // RA-2026-00001
            $t->foreignId('package_id')->constrained('cargo_packages');
            $t->foreignId('invoice_id')->nullable()->constrained('customer_invoices')->nullOnDelete();
            $t->foreignId('branch_id')->constrained('branches');
            $t->string('status')->default('pending'); // pending|approved|on_hold|rejected|released
            $t->text('reason')->nullable();
            $t->string('release_code', 6)->nullable();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->timestamp('code_used_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'branch_id']);
            $t->index('package_id');
        });

        // Effective-dated storage pricing — SOP: first 3 days free, then rate/KG/day.
        Schema::create('storage_rate_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->unsignedInteger('free_days')->default(3);
            $t->decimal('rate_per_kg_day', 10, 2)->default(500);
            $t->string('currency')->default('TZS');
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_rate_settings');
        Schema::dropIfExists('release_approvals');
    }
};
