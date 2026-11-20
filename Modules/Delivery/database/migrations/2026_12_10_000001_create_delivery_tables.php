<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-DRV-
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('branch_id')->constrained('branches');
            $t->string('name');
            $t->string('phone', 40)->nullable();
            $t->string('license_no')->nullable();
            $t->date('license_expiry')->nullable();
            $t->string('category', 30)->nullable();
            $t->json('zones')->nullable();
            $t->string('verification_status', 20)->default('pending'); // pending|verified|rejected
            $t->string('availability', 20)->default('offline'); // offline|available|busy|on_delivery|on_break|suspended
            $t->timestamp('last_active_at')->nullable();
            $t->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $t) {
            $t->id();
            $t->string('registration_no', 30)->unique();
            $t->foreignId('branch_id')->constrained('branches');
            $t->string('type', 30)->nullable();             // bike|van|truck|car
            $t->string('make_model')->nullable();
            $t->decimal('capacity_kg', 10, 2)->default(0);
            $t->unsignedInteger('capacity_packages')->default(0);
            $t->date('insurance_expiry')->nullable();
            $t->date('inspection_expiry')->nullable();
            $t->string('status', 20)->default('active');    // active|maintenance|retired
            $t->timestamps();
        });
        Schema::create('delivery_services', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('pricing_method', 30)->default('zone_fixed'); // zone_fixed|weight|distance|hybrid|contract
            $t->decimal('base_fee', 12, 2)->default(0);
            $t->decimal('per_kg_fee', 12, 2)->default(0);
            $t->decimal('per_km_fee', 12, 2)->default(0);
            $t->decimal('min_fee', 12, 2)->nullable();
            $t->decimal('max_fee', 12, 2)->nullable();
            $t->decimal('max_package_weight', 10, 2)->nullable();
            $t->boolean('requires_signature')->default(true);
            $t->boolean('requires_otp')->default(false);
            $t->boolean('requires_photo')->default(false);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('delivery_zones', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('country')->default('Tanzania');
            $t->string('region')->nullable();
            $t->string('district')->nullable();
            $t->string('city')->nullable();
            $t->string('ward')->nullable();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->decimal('fee', 12, 2)->default(0);
            $t->string('eta')->nullable();
            $t->boolean('remote')->default(false);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['region', 'district', 'city']);
        });
        Schema::create('delivery_orders', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-DLV-TZ-
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('customer_id')->constrained('customers');
            $t->foreignId('address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $t->foreignId('batch_id')->nullable()->constrained('distribution_batches')->nullOnDelete();
            $t->foreignId('service_id')->nullable()->constrained('delivery_services')->nullOnDelete();
            $t->foreignId('zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete();
            $t->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $t->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $t->string('delivery_type', 20)->default('delivery'); // delivery|collection
            $t->decimal('fee', 12, 2)->nullable();
            $t->json('price_breakdown')->nullable();
            $t->decimal('cod_amount', 12, 2)->nullable();
            $t->date('scheduled_date')->nullable();
            $t->text('instructions')->nullable();
            $t->string('status', 30)->default('draft');     // 24-state machine in model
            $t->timestamps();
            $t->index(['branch_id', 'status']);
        });
        Schema::create('delivery_order_packages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $t->foreignId('package_id')->constrained('cargo_packages');
            $t->timestamp('added_at');
            $t->timestamp('removed_at')->nullable();
            $t->timestamps();
            $t->index(['package_id', 'removed_at']);
        });
        Schema::create('delivery_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $t->string('event', 50);
            $t->string('actor_type', 20)->default('staff'); // staff|driver|customer|system
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['order_id', 'event']);
        });
        Schema::create('delivery_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('delivery_orders');
            $t->foreignId('driver_id')->constrained('drivers');
            $t->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $t->string('status', 20)->default('offered');   // offered|accepted|rejected|timeout|cancelled
            $t->timestamp('offered_at');
            $t->timestamp('responded_at')->nullable();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('delivery_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('delivery_orders');
            $t->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $t->string('reason', 40);                        // customer_unavailable|wrong_address|unreachable|refused|access_restricted|payment_pending|damaged|vehicle_failure|driver_incident|other
            $t->string('outcome', 20)->default('failed');   // failed|rescheduled|returned|collected
            $t->string('photo')->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('proof_of_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->unique()->constrained('delivery_orders'); // one POD per order
            $t->string('recipient_name');
            $t->string('recipient_relation', 40)->nullable();
            $t->string('signature_path')->nullable();
            $t->string('photo_path')->nullable();
            $t->string('otp_code', 10)->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->json('scanned_package_ids')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $t->timestamp('delivered_at');
            $t->timestamps();
        });
        Schema::create('delivery_returns', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-RTN-
            $t->foreignId('order_id')->constrained('delivery_orders');
            $t->string('reason', 40);
            $t->json('package_ids');
            $t->string('status', 25)->default('requested'); // requested|approved|collected|in_transit|received_warehouse|reconciled|closed
            $t->text('evidence')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('received_at')->nullable();
            $t->timestamps();
        });
        Schema::create('customer_collections', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-COL-
            $t->foreignId('order_id')->constrained('delivery_orders');
            $t->foreignId('customer_id')->constrained('customers');
            $t->string('collected_by');
            $t->string('id_proof')->nullable();
            $t->string('otp_code', 10)->nullable();
            $t->json('package_ids');
            $t->string('verification_method', 20)->default('id'); // id|otp|rep_authorization
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('collected_at');
            $t->timestamps();
        });
        Schema::create('cod_collections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('delivery_orders');
            $t->decimal('amount', 14, 2);
            $t->string('currency', 3)->default('TZS');
            $t->string('method', 20)->default('cash');      // cash|mobile_money|card
            $t->string('payment_ref')->nullable();
            $t->string('status', 20)->default('collected'); // collected|remitted|reconciled|disputed
            $t->foreignId('collected_by')->nullable()->constrained('drivers')->nullOnDelete();
            $t->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reconciled_at')->nullable();
            $t->timestamps();
        });
        Schema::create('driver_locations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $t->foreignId('order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $t->decimal('lat', 10, 7);
            $t->decimal('lng', 10, 7);
            $t->decimal('accuracy', 8, 2)->nullable();
            $t->timestamp('recorded_at');
            $t->timestamps();
            $t->index(['driver_id', 'recorded_at']);
        });
        Schema::create('delivery_incidents', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-DIN-
            $t->foreignId('order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $t->foreignId('package_id')->nullable()->constrained('cargo_packages')->nullOnDelete();
            $t->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $t->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $t->string('category', 40);
            $t->string('severity', 10)->default('medium');
            $t->text('description')->nullable();
            $t->string('evidence')->nullable();
            $t->string('status', 20)->default('open');
            $t->text('resolution')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['delivery_incidents','driver_locations','cod_collections','customer_collections','delivery_returns','proof_of_deliveries','delivery_attempts','delivery_assignments','delivery_events','delivery_order_packages','delivery_orders','delivery_zones','delivery_services','vehicles','drivers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
