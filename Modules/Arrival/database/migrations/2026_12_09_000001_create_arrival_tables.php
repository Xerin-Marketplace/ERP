<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_arrivals', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-ARR-TZ-2026-000001
            $t->foreignId('shipment_id')->unique()->constrained('shipments'); // no duplicate arrival
            $t->foreignId('branch_id')->constrained('branches');              // TZ branch
            $t->string('mawb')->nullable();
            $t->json('hawbs')->nullable();
            $t->foreignId('airline_id')->nullable()->constrained('airlines')->nullOnDelete();
            $t->string('flight_no', 20)->nullable();
            $t->string('origin_airport', 10)->nullable();
            $t->string('destination_airport', 10)->nullable();
            $t->timestamp('scheduled_arrival')->nullable();
            $t->timestamp('actual_arrival')->nullable();
            $t->string('arrival_source', 30)->default('manual'); // airline_api|manual|airport|tracking
            $t->unsignedInteger('expected_packages')->default(0);
            $t->decimal('expected_weight', 12, 2)->nullable();
            $t->unsignedInteger('received_packages')->default(0);
            $t->string('status', 30)->default('expected');  // expected|in_transit|arrived|manifest_verification|partially_received|fully_received|discrepancy_detected|on_hold|collection_pending|collected|transferred_to_warehouse|closed
            $t->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('arrival_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('arrival_id')->constrained('shipment_arrivals')->cascadeOnDelete();
            $t->string('event', 50);
            $t->string('source', 30)->default('manual');
            $t->json('meta')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['arrival_id', 'event']);
        });
        Schema::create('arrival_discrepancies', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-DSC-
            $t->foreignId('arrival_id')->constrained('shipment_arrivals')->cascadeOnDelete();
            $t->foreignId('package_id')->nullable()->constrained('cargo_packages')->nullOnDelete();
            $t->string('kind', 30);                          // missing|excess|damaged|wrong_awb|wrong_shipment|unidentified|weight_mismatch|duplicate_scan
            $t->decimal('expected_qty', 12, 2)->nullable();
            $t->decimal('actual_qty', 12, 2)->nullable();
            $t->text('description')->nullable();
            $t->string('evidence')->nullable();
            $t->string('severity', 10)->default('medium');
            $t->string('status', 20)->default('open');
            $t->text('resolution')->nullable();
            $t->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
        Schema::create('import_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('arrival_id')->constrained('shipment_arrivals')->cascadeOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('type', 40);                          // mawb|hawb|commercial_invoice|packing_list|import_declaration|customs_assessment|customs_release|permit|duty_payment_evidence|delivery_order|airport_release|other
            $t->string('document_number')->nullable();
            $t->string('issuing_authority')->nullable();
            $t->date('issue_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->string('path')->nullable();
            $t->string('original_name')->nullable();
            $t->string('review_status', 20)->default('pending'); // pending|approved|rejected
            $t->text('notes')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('clearing_agents', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('license_ref')->nullable();
            $t->string('contact_name')->nullable();
            $t->string('contact_phone', 40)->nullable();
            $t->string('contact_email')->nullable();
            $t->date('license_expiry')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });
        Schema::create('customs_cases', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-CLR-
            $t->foreignId('arrival_id')->constrained('shipment_arrivals');
            $t->foreignId('agent_id')->nullable()->constrained('clearing_agents')->nullOnDelete();
            $t->string('declaration_ref')->nullable();      // never auto-generated
            $t->date('declaration_date')->nullable();
            $t->string('assessment_ref')->nullable();
            $t->decimal('assessment_amount', 14, 2)->nullable();
            $t->decimal('duty_amount', 14, 2)->nullable();
            $t->decimal('tax_amount', 14, 2)->nullable();
            $t->string('currency', 3)->default('TZS');
            $t->boolean('inspection_required')->default(false);
            $t->string('payment_evidence')->nullable();     // required before release
            $t->string('release_evidence')->nullable();     // required for released status
            $t->string('status', 30)->default('not_started'); // not_started|documents_pending|ready_for_submission|submitted|under_assessment|additional_info_required|inspection_required|payment_pending|payment_recorded|awaiting_release|released|rejected|on_hold|cancelled
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('customs_case_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('case_id')->constrained('customs_cases')->cascadeOnDelete();
            $t->string('event', 50);
            $t->string('evidence')->nullable();
            $t->json('meta')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('customs_charges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('arrival_id')->constrained('shipment_arrivals');
            $t->foreignId('case_id')->nullable()->constrained('customs_cases')->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('category', 40);                      // customs_duty|import_tax|processing_fee|agent_fee|airport_handling|storage|inspection|other
            $t->decimal('amount', 14, 2);
            $t->string('currency', 3)->default('TZS');
            $t->decimal('fx_rate', 12, 6)->nullable();
            $t->string('source')->nullable();
            $t->string('supporting_doc')->nullable();
            $t->string('allocation_method', 20)->nullable(); // chargeable_weight|actual_weight|package_count|manual
            $t->string('approval_status', 20)->default('pending');
            $t->string('payment_status', 20)->default('unpaid');
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('airport_collections', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-APC-
            $t->foreignId('arrival_id')->constrained('shipment_arrivals');
            $t->foreignId('agent_id')->nullable()->constrained('clearing_agents')->nullOnDelete();
            $t->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('vehicle')->nullable();
            $t->string('driver')->nullable();
            $t->string('airport_release_ref')->nullable();  // required to approve
            $t->unsignedInteger('expected_packages')->default(0);
            $t->unsignedInteger('collected_packages')->default(0);
            $t->json('scanned_package_ids')->nullable();
            $t->string('status', 25)->default('requested');  // requested|approved|dispatched|collected|delivered_warehouse|cancelled
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('collected_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('package_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->unique()->constrained('cargo_packages'); // prevents duplicate physical receiving
            $t->foreignId('arrival_id')->constrained('shipment_arrivals');
            $t->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $t->string('condition', 20)->default('good');    // good|damaged|unidentified
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('received_at');
            $t->timestamps();
        });
        Schema::create('distribution_batches', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-DBT-
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('grouping', 30)->default('customer'); // customer|zone|region|district|city|service_type
            $t->string('zone')->nullable();
            $t->string('status', 20)->default('open');       // open|ready|handed_over|cancelled
            $t->timestamp('handed_over_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('distribution_batch_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained('distribution_batches')->cascadeOnDelete();
            $t->foreignId('package_id')->constrained('cargo_packages');
            $t->timestamps();
            $t->unique(['batch_id', 'package_id']);
        });
        Schema::create('release_overrides', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->constrained('cargo_packages');
            $t->string('check', 50);
            $t->text('reason');
            $t->foreignId('user_id')->constrained('users');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['release_overrides','distribution_batch_items','distribution_batches','package_receipts','airport_collections','customs_charges','customs_case_events','customs_cases','clearing_agents','import_documents','arrival_discrepancies','arrival_events','shipment_arrivals'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
