<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargo_pre_alerts', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();              // XER-PA-2026-000001
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->foreignId('handover_id')->nullable()->constrained('commercial_handovers')->nullOnDelete();
            $t->foreignId('inquiry_id')->nullable()->constrained('inquiries')->nullOnDelete();
            $t->string('supplier_name')->nullable();
            $t->string('supplier_contact')->nullable();
            $t->string('supplier_ref')->nullable();             // PO / supplier invoice ref
            $t->foreignId('branch_id')->constrained('branches');
            $t->date('expected_date')->nullable();
            $t->string('expected_time_window', 50)->nullable();
            $t->unsignedInteger('expected_packages')->nullable();
            $t->text('cargo_description')->nullable();
            $t->decimal('expected_weight', 10, 2)->nullable();
            $t->string('vehicle_info')->nullable();
            $t->string('driver_info')->nullable();
            $t->text('delivery_instructions')->nullable();
            $t->string('status', 30)->default('draft');         // draft|submitted|approved|awaiting_delivery|scheduled|partially_received|fully_received|cancelled|expired|on_hold
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('cargo_receivings', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();              // XER-RCV-2026-000001
            $t->foreignId('pre_alert_id')->nullable()->constrained('cargo_pre_alerts')->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->foreignId('handover_id')->nullable()->constrained('commercial_handovers')->nullOnDelete();
            $t->string('supplier_name')->nullable();
            $t->string('delivery_note_ref')->nullable();
            $t->string('supplier_invoice_ref')->nullable();
            $t->string('seal_info')->nullable();
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('received_at')->nullable();
            $t->unsignedInteger('expected_packages')->default(0);
            $t->unsignedInteger('actual_packages')->default(0);
            $t->string('outcome', 30)->nullable();              // fully|partially|excess|short|damaged|wrong_customer|unidentified|rejected
            $t->string('condition', 30)->default('good');       // good|minor_damage|damaged
            $t->string('status', 30)->default('in_progress');   // in_progress|confirmed|on_hold|cancelled
            $t->boolean('unidentified')->default(false);
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('cargo_packages', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();              // XER-DXB-PKG-2026-000001
            $t->foreignId('receiving_id')->nullable()->constrained('cargo_receivings')->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->foreignId('handover_id')->nullable()->constrained('commercial_handovers')->nullOnDelete();
            $t->foreignId('branch_id')->constrained('branches');
            $t->unsignedInteger('sequence')->default(1);        // package N of M
            $t->string('package_type', 40)->default('carton');  // carton|wooden_box|pallet|bag|crate|other
            $t->string('cargo_category', 50)->nullable();
            $t->text('goods_description')->nullable();
            $t->decimal('gross_weight', 10, 2)->nullable();
            $t->decimal('net_weight', 10, 2)->nullable();
            $t->decimal('length', 10, 2)->nullable();
            $t->decimal('width', 10, 2)->nullable();
            $t->decimal('height', 10, 2)->nullable();
            $t->decimal('volumetric_weight', 10, 2)->nullable();
            $t->decimal('chargeable_weight', 10, 2)->nullable();
            $t->unsignedInteger('volumetric_divisor')->nullable();
            $t->string('handling', 40)->nullable();             // fragile|liquid|battery|perishable|dangerous|normal
            $t->string('inspection_status', 30)->default('pending');   // pending|in_progress|passed|passed_conditions|info_required|repack_required|compliance_review|rejected|on_hold
            $t->string('packaging_status', 30)->default('original');   // original|repack_required|repack_in_progress|repacked
            $t->unsignedBigInteger('location_id')->nullable()->index();  // FK added after warehouse_locations (Postgres ordering)
            $t->string('status', 30)->default('received');      // expected|received|identified|weighed|inspection_passed|on_hold|stored|ready|allocated|dispatched|cancelled
            $t->boolean('unidentified')->default(false);
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('cargo_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->nullable()->constrained('cargo_packages')->cascadeOnDelete();
            $t->foreignId('receiving_id')->nullable()->constrained('cargo_receivings')->cascadeOnDelete();
            $t->string('event', 50);                            // created|scanned|received|weighed|reweighed|inspected|hold|released|repacked|moved|stored|ready|allocated|linked
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('location_ref')->nullable();             // location code or scan point
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['package_id', 'event']);
        });

        Schema::create('cargo_measurements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->constrained('cargo_packages')->cascadeOnDelete();
            $t->string('kind', 10)->default('initial');         // initial|reweigh|remeasure
            $t->decimal('gross_weight', 10, 2)->nullable();
            $t->decimal('length', 10, 2)->nullable();
            $t->decimal('width', 10, 2)->nullable();
            $t->decimal('height', 10, 2)->nullable();
            $t->decimal('volumetric_weight', 10, 2)->nullable();
            $t->decimal('chargeable_weight', 10, 2)->nullable();
            $t->unsignedInteger('divisor')->default(6000);
            $t->string('reason')->nullable();
            $t->foreignId('measured_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('cargo_inspections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->constrained('cargo_packages')->cascadeOnDelete();
            $t->json('checklist')->nullable();                  // {condition, integrity, fragile, liquid, battery, perishable, dangerous, packaging_ok, docs_ok}
            $t->string('outcome', 30);                          // passed|passed_conditions|info_required|repack_required|compliance_review|rejected|on_hold
            $t->text('comments')->nullable();
            $t->text('required_actions')->nullable();
            $t->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('cargo_repackagings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->constrained('cargo_packages')->cascadeOnDelete();
            $t->string('reason')->nullable();
            $t->string('packaging_type', 40)->nullable();
            $t->json('materials')->nullable();                  // [{item, qty}]
            $t->json('before_snapshot')->nullable();            // original weight/dims
            $t->decimal('new_gross_weight', 10, 2)->nullable();
            $t->decimal('new_length', 10, 2)->nullable();
            $t->decimal('new_width', 10, 2)->nullable();
            $t->decimal('new_height', 10, 2)->nullable();
            $t->decimal('cost', 10, 2)->nullable();
            $t->foreignId('repacked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name');
            $t->foreignId('branch_id')->constrained('branches');
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });

        Schema::create('warehouse_locations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $t->string('code', 40);                             // A-A01-02-05
            $t->string('name')->nullable();
            $t->string('zone', 10)->nullable();
            $t->string('type', 20)->default('storage');         // receiving|inspection|storage|hold|repacking|dispatch|quarantine
            $t->unsignedInteger('capacity')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['warehouse_id', 'code']);
        });

        Schema::table('cargo_packages', function (Blueprint $t) {
            $t->foreign('location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
        });

        Schema::create('warehouse_movements', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();              // XER-MOV-2026-000001
            $t->foreignId('package_id')->constrained('cargo_packages');
            $t->foreignId('from_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $t->foreignId('to_location_id')->constrained('warehouse_locations');
            $t->string('reason', 40)->default('transfer');      // receive_to_inspection|inspection_to_storage|storage_to_repack|repack_to_storage|storage_to_pick|pick_to_dispatch|transfer|quarantine|hold|release
            $t->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('cargo_incidents', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();              // XER-INC-2026-000001
            $t->foreignId('package_id')->nullable()->constrained('cargo_packages')->nullOnDelete();
            $t->foreignId('receiving_id')->nullable()->constrained('cargo_receivings')->nullOnDelete();
            $t->foreignId('branch_id')->constrained('branches');
            $t->string('category', 40);                         // short_received|excess|wrong_customer|unidentified|damaged|missing_label|dup_reference|incorrect_weight|packaging_failure|restricted|missing_docs|location_conflict|missing_package|unauthorized_movement
            $t->string('severity', 10)->default('medium');      // low|medium|high|critical
            $t->text('description')->nullable();
            $t->string('status', 20)->default('open');          // open|investigating|resolved|closed
            $t->text('resolution')->nullable();
            $t->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['cargo_incidents','warehouse_movements','warehouse_locations','warehouses','cargo_repackagings','cargo_inspections','cargo_measurements','cargo_events','cargo_packages','cargo_receivings','cargo_pre_alerts'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
