<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spec §8.3 — inter-branch ledger. Dubai spends (packing, airline,
     * passenger fee); Tanzania collects revenue. Entries reconcile
     * monthly; settlement posts a balanced intercompany journal.
     */
    public function up(): void
    {
        Schema::create('inter_branch_entries', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();              // IB-2026-000001
            $t->foreignId('company_id')->constrained('companies');
            $t->foreignId('spent_branch_id')->constrained('branches');   // incurred the cost (e.g. DXB)
            $t->foreignId('owed_branch_id')->constrained('branches');    // benefits / owes (e.g. TZ)
            $t->string('description');
            $t->decimal('amount', 14, 2);
            $t->string('currency', 3)->default('USD');
            $t->string('source_type', 40)->nullable();      // cargo_trip|cargo_box|shipment|manual
            $t->unsignedBigInteger('source_id')->nullable();
            $t->date('entry_date');
            $t->string('status', 15)->default('open');      // open|settled|void
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->timestamp('settled_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'entry_date']);
            $t->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inter_branch_entries');
    }
};
