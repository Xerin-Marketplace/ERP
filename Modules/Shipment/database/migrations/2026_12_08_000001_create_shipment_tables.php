<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airlines', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code', 10)->unique();               // e.g. EK
            $t->string('iata_code', 3)->nullable();
            $t->string('icao_code', 4)->nullable();
            $t->string('contact_email')->nullable();
            $t->string('contact_phone', 40)->nullable();
            $t->json('routes')->nullable();                 // [{from, to}]
            $t->text('cargo_restrictions')->nullable();
            $t->string('contract_ref')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });

        Schema::create('shipments', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-SHP-2026-000001
            $t->foreignId('origin_branch_id')->constrained('branches');
            $t->foreignId('destination_branch_id')->nullable()->constrained('branches');
            $t->string('type', 30)->default('consolidated'); // consolidated|single|partial
            $t->string('transport_mode', 20)->default('air');
            $t->string('origin_airport', 10)->nullable();   // DXB
            $t->string('destination_airport', 10)->nullable(); // DAR
            $t->foreignId('airline_id')->nullable()->constrained('airlines')->nullOnDelete();
            $t->date('planned_departure')->nullable();
            $t->date('planned_arrival')->nullable();
            $t->timestamp('actual_departure')->nullable();
            $t->timestamp('actual_arrival')->nullable();
            $t->unsignedInteger('total_packages')->default(0);
            $t->decimal('total_gross_weight', 12, 2)->default(0);
            $t->decimal('total_volumetric_weight', 12, 2)->default(0);
            $t->decimal('total_chargeable_weight', 12, 2)->default(0);
            $t->decimal('declared_value', 14, 2)->nullable();
            $t->string('currency', 3)->nullable();
            $t->string('status', 30)->default('draft');
            $t->foreignId('parent_id')->nullable()->constrained('shipments')->nullOnDelete(); // split lineage
            $t->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('shipment_packages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $t->foreignId('package_id')->constrained('cargo_packages');
            $t->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('allocated_at');
            $t->timestamp('removed_at')->nullable();
            $t->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('remove_reason')->nullable();
            $t->timestamps();
            $t->index(['package_id', 'removed_at']);
        });

        Schema::create('shipment_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $t->string('event', 50);
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['shipment_id', 'event']);
        });

        Schema::create('airline_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-BKG-2026-000001
            $t->foreignId('shipment_id')->constrained('shipments');
            $t->foreignId('airline_id')->constrained('airlines');
            $t->string('route')->nullable();
            $t->date('planned_departure')->nullable();
            $t->unsignedInteger('package_count')->default(0);
            $t->decimal('total_weight', 12, 2)->default(0);
            $t->decimal('chargeable_weight', 12, 2)->default(0);
            $t->string('cargo_category', 50)->nullable();
            $t->text('handling_requirements')->nullable();
            $t->string('status', 30)->default('draft');     // draft|submitted|pending_confirmation|confirmed|rejected|cancelled|rescheduled|expired
            $t->string('booking_ref')->nullable();        // airline confirmation ref
            $t->string('flight_no', 20)->nullable();
            $t->text('airline_response')->nullable();
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('confirmed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('air_waybills', function (Blueprint $t) {
            $t->id();
            $t->string('awb_number', 30);                 // e.g. 176-12345675
            $t->string('kind', 10)->default('hawb');      // hawb|mawb
            $t->foreignId('shipment_id')->constrained('shipments');
            $t->foreignId('booking_id')->nullable()->constrained('airline_bookings')->nullOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('air_waybills')->nullOnDelete(); // hawb→mawb
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('shipper')->nullable();
            $t->string('consignee')->nullable();
            $t->string('origin', 10)->nullable();
            $t->string('destination', 10)->nullable();
            $t->unsignedInteger('package_count')->default(0);
            $t->decimal('gross_weight', 12, 2)->nullable();
            $t->decimal('chargeable_weight', 12, 2)->nullable();
            $t->decimal('declared_value', 14, 2)->nullable();
            $t->text('goods_description')->nullable();
            $t->text('handling_info')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->string('status', 20)->default('draft');   // draft|pending_review|approved|issued|amended|cancelled|closed
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('issued_at')->nullable();
            $t->timestamps();
            $t->unique(['awb_number', 'version']);
        });

        Schema::create('shipment_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('type', 40);                       // commercial_invoice|packing_list|awb|export_declaration|export_permit|inspection_cert|restricted_goods|dangerous_goods|other
            $t->string('reference')->nullable();
            $t->string('issuer')->nullable();
            $t->date('issue_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->string('path')->nullable();
            $t->string('original_name')->nullable();
            $t->string('review_status', 20)->default('pending'); // pending|approved|rejected
            $t->text('review_notes')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('cargo_manifests', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();        // XER-MNF-2026-000001
            $t->foreignId('shipment_id')->constrained('shipments');
            $t->foreignId('booking_id')->nullable()->constrained('airline_bookings')->nullOnDelete();
            $t->foreignId('flight_id')->nullable();
            $t->string('destination', 10)->nullable();
            $t->unsignedInteger('total_pieces')->default(0);
            $t->decimal('total_gross_weight', 12, 2)->default(0);
            $t->decimal('total_chargeable_weight', 12, 2)->default(0);
            $t->json('reconciliation')->nullable();       // expected vs actual counts/weights
            $t->unsignedInteger('version')->default(1);
            $t->string('status', 20)->default('draft');   // draft|reviewed|approved|issued|accepted|amended|cancelled
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamps();
        });

        Schema::create('airport_dispatches', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();        // XER-DSP-2026-000001
            $t->foreignId('shipment_id')->constrained('shipments');
            $t->foreignId('branch_id')->constrained('branches');
            $t->string('vehicle')->nullable();
            $t->string('driver')->nullable();
            $t->unsignedInteger('expected_packages')->default(0);
            $t->unsignedInteger('scanned_packages')->default(0);
            $t->string('status', 30)->default('scheduled'); // scheduled|loading|departed|arrived|handed_over|cancelled
            $t->timestamp('departed_at')->nullable();
            $t->timestamp('arrived_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('airline_handovers', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();        // XER-AHO-2026-000001
            $t->foreignId('shipment_id')->constrained('shipments');
            $t->foreignId('manifest_id')->nullable()->constrained('cargo_manifests')->nullOnDelete();
            $t->foreignId('airline_id')->nullable()->constrained('airlines')->nullOnDelete();
            $t->string('airport', 10)->nullable();
            $t->unsignedInteger('expected_packages')->default(0);
            $t->unsignedInteger('accepted_packages')->default(0);
            $t->decimal('accepted_weight', 12, 2)->nullable();
            $t->json('rejected_packages')->nullable();    // [package_id => reason]
            $t->json('missing_packages')->nullable();
            $t->string('status', 20)->default('partial'); // partial|complete|failed
            $t->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('airline_rep')->nullable();
            $t->text('receipt_evidence')->nullable();
            $t->timestamps();
        });

        Schema::create('flights', function (Blueprint $t) {
            $t->id();
            $t->foreignId('airline_id')->nullable()->constrained('airlines')->nullOnDelete();
            $t->string('flight_no', 20);
            $t->string('departure_airport', 10)->nullable();
            $t->string('arrival_airport', 10)->nullable();
            $t->timestamp('scheduled_departure')->nullable();
            $t->timestamp('actual_departure')->nullable();
            $t->timestamp('scheduled_arrival')->nullable();
            $t->timestamp('actual_arrival')->nullable();
            $t->string('status', 20)->default('scheduled'); // scheduled|boarding|departed|in_transit|landed|delayed|cancelled|diverted|offloaded|rescheduled
            $t->timestamps();
            $t->index(['flight_no', 'scheduled_departure']);
        });

        Schema::create('flight_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('flight_id')->constrained('flights')->cascadeOnDelete();
            $t->string('event', 40);
            $t->string('source', 30)->default('manual');  // airline_api|manual|airport|tracking_provider
            $t->json('meta')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('shipment_exceptions', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();        // XER-EXC-2026-000001
            $t->foreignId('shipment_id')->constrained('shipments');
            $t->json('package_ids')->nullable();
            $t->string('category', 40);                   // booking_rejected|flight_cancelled|flight_delayed|offloaded|missing_packages|damaged|manifest_mismatch|awb_discrepancy|document_rejected|handover_failed|route_change|transshipment
            $t->string('severity', 10)->default('medium');
            $t->text('description')->nullable();
            $t->string('status', 20)->default('open');
            $t->text('resolution')->nullable();
            $t->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['shipment_exceptions','flight_events','flights','airline_handovers','airport_dispatches','cargo_manifests','shipment_documents','air_waybills','airline_bookings','shipment_events','shipment_packages','shipments','airlines'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
