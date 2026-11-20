<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_clarifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('direction', 10); // received|sent
            $t->string('subject');
            $t->text('content');
            $t->text('response')->nullable();
            $t->date('received_at')->nullable();
            $t->date('responded_at')->nullable();
            $t->string('status', 20)->default('open'); // open|responded|closed
            $t->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('tender_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('title');
            $t->string('raci', 10)->default('A'); // R|A|C|I
            $t->foreignId('assignee_id')->constrained('users');
            $t->dateTime('due_at')->nullable();
            $t->string('status', 20)->default('open'); // open|in_progress|done|blocked
            $t->text('note')->nullable();
            $t->timestamps();
        });

        Schema::create('tender_awards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('award_ref')->nullable();
            $t->date('awarded_at');
            $t->decimal('value', 18, 2);
            $t->string('currency', 3)->default('TZS');
            $t->text('scope')->nullable();
            $t->text('conditions')->nullable();
            $t->date('contract_sign_deadline')->nullable();
            $t->foreignId('notice_document_id')->nullable()->constrained('tender_documents')->nullOnDelete();
            $t->foreignId('recorded_by')->constrained('users');
            $t->timestamps();
        });

        Schema::create('tender_contract_variations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contract_id')->constrained('tender_contracts')->cascadeOnDelete();
            $t->string('ref')->nullable();
            $t->string('type', 30); // scope|value|time|payment_terms|other
            $t->text('description');
            $t->decimal('value_delta', 18, 2)->default(0);
            $t->integer('days_delta')->default(0);
            $t->string('status', 20)->default('draft'); // draft|approved|rejected
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('tender_document_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('tender_documents')->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->string('path');
            $t->string('original_name');
            $t->unsignedBigInteger('size');
            $t->text('change_note')->nullable();
            $t->foreignId('uploaded_by')->constrained('users');
            $t->timestamps();
        });

        Schema::create('tender_supplier_rfqs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('ref');
            $t->date('issued_at')->nullable();
            $t->date('due_at')->nullable();
            $t->string('status', 20)->default('open'); // open|closed
            $t->text('scope')->nullable();
            $t->timestamps();
        });

        // link quotations to RFQs + BOQ items
        Schema::table('tender_quotations', function (Blueprint $t) {
            $t->foreignId('rfq_id')->nullable()->after('tender_id')->constrained('tender_supplier_rfqs')->nullOnDelete();
            $t->foreignId('boq_item_id')->nullable()->after('rfq_id')->constrained('tender_boq_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tender_quotations', fn (Blueprint $t) => $t->dropConstrainedForeignId('rfq_id'));
        Schema::table('tender_quotations', fn (Blueprint $t) => $t->dropConstrainedForeignId('boq_item_id'));
        foreach (['tender_supplier_rfqs','tender_document_versions','tender_contract_variations',
                  'tender_awards','tender_tasks','tender_clarifications'] as $tb) {
            Schema::dropIfExists($tb);
        }
    }
};
