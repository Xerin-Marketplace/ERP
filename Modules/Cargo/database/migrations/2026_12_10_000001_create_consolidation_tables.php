<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer bookings — the pre-arrival shipment request (BK-2026-000001).
        Schema::create('cargo_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('customer_id')->constrained('customers');
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $t->string('service')->default('air_cargo');
            $t->string('carriage_mode')->default('passenger'); // passenger|air_cargo|sea|courier
            $t->string('origin')->default('DXB');
            $t->string('destination')->default('DAR');
            $t->string('collection_type')->default('drop_off'); // drop_off|pickup
            $t->string('supplier_name')->nullable();
            $t->string('supplier_order_ref')->nullable();
            $t->text('goods_description')->nullable();
            $t->unsignedInteger('expected_packages')->default(1);
            $t->decimal('declared_weight', 10, 2)->nullable();
            $t->string('status')->default('pending'); // pending|confirmed|in_transit|closed|cancelled
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'customer_id']);
        });

        // Consolidation boxes — BX-DXB-2026-00001, unique company-wide.
        Schema::create('cargo_boxes', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $t->string('box_type')->default('carton');
            $t->decimal('target_weight', 8, 2)->default(23);
            $t->decimal('actual_weight', 8, 2)->nullable();
            $t->string('seal_number')->nullable();
            $t->string('status')->default('open'); // open|closed|assigned|handed_over|departed|arrived|opened|closed_out
            $t->foreignId('trip_id')->nullable();
            $t->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('closed_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['status', 'branch_id']);
        });

        Schema::create('cargo_box_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('box_id')->constrained('cargo_boxes')->cascadeOnDelete();
            $t->foreignId('package_id')->constrained('cargo_packages');
            $t->decimal('weight', 8, 2)->nullable();
            $t->timestamp('added_at')->nullable();
            $t->timestamps();
            $t->unique(['box_id', 'package_id']);
        });

        // Passenger-carry (MCO) trips — TR-2026-0001.
        Schema::create('cargo_trips', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('origin_branch_id')->constrained('branches');
            $t->foreignId('destination_branch_id')->constrained('branches');
            $t->string('passenger_name');
            $t->string('passenger_id_doc')->nullable();
            $t->string('passenger_phone')->nullable();
            $t->string('airline')->nullable();
            $t->string('flight_number')->nullable();
            $t->date('flight_date')->nullable();
            $t->decimal('baggage_allowance', 8, 2)->nullable();
            $t->decimal('passenger_fee', 12, 2)->nullable();
            $t->string('currency')->default('AED');
            $t->string('status')->default('planned'); // planned|assigned|handed_over|departed|arrived|reconciled|closed
            $t->foreignId('handover_dxb_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('handover_dxb_at')->nullable();
            $t->foreignId('handover_tz_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('handover_tz_at')->nullable();
            $t->timestamp('departed_at')->nullable();
            $t->timestamp('arrived_at')->nullable();
            $t->string('departure_evidence_file_id')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['status', 'flight_date']);
        });

        Schema::table('cargo_boxes', function (Blueprint $t) {
            $t->foreign('trip_id')->references('id')->on('cargo_trips')->nullOnDelete();
        });

        // Link packages to their booking + unmatched queue already exists via `unidentified`.
        Schema::table('cargo_packages', function (Blueprint $t) {
            $t->foreignId('booking_id')->nullable()->after('receiving_id')->constrained('cargo_bookings')->nullOnDelete();
            $t->string('tracking_no')->nullable()->after('booking_id');
            $t->index('tracking_no');
        });
    }

    public function down(): void
    {
        Schema::table('cargo_packages', function (Blueprint $t) {
            $t->dropConstrainedForeignId('booking_id');
            $t->dropColumn('tracking_no');
        });
        Schema::table('cargo_boxes', fn (Blueprint $t) => $t->dropForeign(['trip_id']));
        Schema::dropIfExists('cargo_trips');
        Schema::dropIfExists('cargo_box_lines');
        Schema::dropIfExists('cargo_boxes');
        Schema::dropIfExists('cargo_bookings');
    }
};
