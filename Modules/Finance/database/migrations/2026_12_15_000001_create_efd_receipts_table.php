<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // TRA EFD / fiscal receipts (spec §15.4 groundwork). Transmission
        // is deferred until tax-adviser requirements are confirmed —
        // receipts stay queued locally with their full payload + evidence.
        Schema::create('efd_receipts', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();            // EFD-YYYY-######
            $t->foreignId('customer_invoice_id')->constrained('customer_invoices')->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('status', 20)->default('draft');       // draft|queued|transmitted|acknowledged|failed|void
            $t->string('serial_number', 60)->nullable();      // EFD device serial (when assigned)
            $t->string('receipt_number', 40)->nullable();     // fiscal receipt number from device/TRA
            $t->string('verification_code', 60)->nullable();  // TRA verification code/QR payload
            $t->json('payload');                              // TRA-oriented receipt payload
            $t->json('response')->nullable();                 // gateway response evidence
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('queued_at')->nullable();
            $t->timestamp('transmitted_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efd_receipts');
    }
};
