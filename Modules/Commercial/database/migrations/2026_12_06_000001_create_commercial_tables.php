<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Customers ────────────────────────────────────────────────
        // Extend Finance's existing `customers` (AR counterparty) — do NOT
        // duplicate the master record. Adds CRM profile fields only.
        Schema::table('customers', function (Blueprint $t) {
            $t->string('type', 30)->default('business')->after('name');   // individual|business|agent|wholesale
            $t->string('trading_name')->nullable()->after('type');
            $t->string('category', 50)->nullable()->after('trading_name');
            $t->string('whatsapp', 40)->nullable();
            $t->string('preferred_channel', 20)->nullable();
            $t->string('preferred_language', 10)->default('en');
            $t->string('country')->nullable();
            $t->string('city')->nullable();
            $t->string('registration_no')->nullable();
            $t->string('contact_person')->nullable();
            $t->string('status', 20)->default('prospect')->index();       // prospect|active|inactive|on_hold|restricted|archived
            $t->string('source', 40)->nullable();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->softDeletes();
        });
        Schema::create('customer_contacts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('role')->nullable();
            $t->string('email')->nullable();
            $t->string('phone', 40)->nullable();
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
        });
        Schema::create('customer_addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('type', 20)->default('billing');          // billing|delivery|registered|other
            $t->string('country')->nullable();
            $t->string('city')->nullable();
            $t->string('address')->nullable();
            $t->string('postal_code')->nullable();
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
        });
        Schema::create('customer_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 40);                              // id|registration|tax|contract|other
            $t->string('path');
            $t->string('original_name')->nullable();
            $t->unsignedInteger('size')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // ── Leads / CRM ──────────────────────────────────────────────
        Schema::create('leads', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();               // XER-LED-2026-000001
            $t->string('name');
            $t->string('contact_person')->nullable();
            $t->string('phone', 40)->nullable()->index();
            $t->string('email')->nullable()->index();
            $t->string('company')->nullable();
            $t->string('source', 40)->nullable();                // website|whatsapp|phone|email|walkin|social|referral|agent
            $t->string('interested_service', 60)->nullable();
            $t->string('origin')->nullable();
            $t->string('destination')->nullable();
            $t->string('estimated_volume')->nullable();
            $t->decimal('estimated_value', 14, 2)->nullable();
            $t->string('stage', 30)->default('new');             // new|contacted|qualified|needs_assessment|quote_requested|quote_sent|negotiation|won|lost|disqualified
            $t->string('priority', 10)->default('normal');       // low|normal|high
            $t->timestamp('follow_up_at')->nullable()->index();
            $t->string('lost_reason')->nullable();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->timestamp('converted_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('lead_activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 30);                              // call|meeting|email|whatsapp|note|stage_change|converted
            $t->text('body')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        // ── Inquiries ────────────────────────────────────────────────
        Schema::create('inquiries', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();               // XER-INQ-2026-000001
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $t->string('contact_person')->nullable();
            $t->string('phone', 40)->nullable();
            $t->string('email')->nullable();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('origin_country')->nullable();
            $t->string('origin_city')->nullable();
            $t->string('destination_country')->nullable();
            $t->string('destination_city')->nullable();
            $t->string('cargo_category', 50)->nullable();        // air|express|general|commercial|personal|special
            $t->text('cargo_description')->nullable();
            $t->unsignedInteger('package_count')->nullable();
            $t->decimal('estimated_weight', 10, 2)->nullable();
            $t->decimal('estimated_volume', 10, 3)->nullable();
            $t->decimal('cargo_value', 14, 2)->nullable();
            $t->string('service_type', 50)->nullable();
            $t->date('expected_ship_date')->nullable();
            $t->date('required_delivery_date')->nullable();
            $t->text('special_handling')->nullable();
            $t->string('source', 40)->default('manual');         // website|portal|whatsapp|phone|email|walkin|agent|manual
            $t->string('priority', 10)->default('normal');
            $t->string('status', 30)->default('new');            // see Inquiry::STATUSES
            $t->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('inquiry_activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('event', 40);                             // created|assigned|contacted|updated|assessed|quoted|status_change
            $t->text('body')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });
        Schema::create('inquiry_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('original_name')->nullable();
            $t->unsignedInteger('size')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('cargo_assessments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $t->boolean('fragile')->default(false);
            $t->boolean('liquid')->default(false);
            $t->boolean('battery')->default(false);
            $t->boolean('perishable')->default(false);
            $t->boolean('dangerous_declared')->default(false);
            $t->text('packaging_requirements')->nullable();
            $t->string('outcome', 40);                           // eligible|eligible_conditions|info_required|manual_review|rejected
            $t->text('conditions')->nullable();
            $t->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // ── Rate cards / pricing ─────────────────────────────────────
        Schema::create('rate_cards', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->string('origin_country')->nullable();
            $t->string('origin_city')->nullable();
            $t->string('destination_country')->nullable();
            $t->string('destination_city')->nullable();
            $t->string('service_type', 50)->nullable();
            $t->string('cargo_category', 50)->nullable();
            $t->string('customer_category', 50)->nullable();
            $t->string('currency', 3)->default('USD');
            $t->decimal('base_rate', 12, 2)->default(0);         // flat charge
            $t->decimal('per_kg_rate', 12, 4)->default(0);
            $t->decimal('minimum_charge', 12, 2)->default(0);
            $t->json('surcharges')->nullable();                  // [{key,label,amount,type:flat|percent}]
            $t->unsignedInteger('volumetric_divisor')->default(6000); // cm³/kg
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->unsignedSmallInteger('priority')->default(100);  // lower = preferred match
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();
            $t->string('status', 20)->default('draft');          // draft|pending_approval|approved|active|expired|archived
            $t->unsignedInteger('version')->default(1);
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        // ── Quotations ───────────────────────────────────────────────
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();                  // XER-QUO-2026-000001
            $t->unsignedInteger('version')->default(1);
            $t->foreignId('parent_id')->nullable()->constrained('quotations')->nullOnDelete(); // prev version
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('inquiry_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('rate_card_id')->nullable()->constrained('rate_cards')->nullOnDelete();
            $t->string('origin')->nullable();
            $t->string('destination')->nullable();
            $t->text('cargo_description')->nullable();
            $t->unsignedInteger('package_count')->nullable();
            $t->decimal('gross_weight', 10, 2)->nullable();
            $t->decimal('volumetric_weight', 10, 2)->nullable();
            $t->decimal('chargeable_weight', 10, 2)->nullable();
            $t->unsignedInteger('volumetric_divisor')->nullable();
            $t->string('currency', 3)->default('USD');
            $t->decimal('subtotal', 14, 2)->default(0);
            $t->decimal('discount', 14, 2)->default(0);
            $t->decimal('surcharge_total', 14, 2)->default(0);
            $t->decimal('total', 14, 2)->default(0);
            $t->json('breakdown')->nullable();                   // explainable pricing lines
            $t->json('pricing_snapshot')->nullable();            // immutable rate-card snapshot
            $t->date('valid_until')->nullable();
            $t->text('payment_terms')->nullable();
            $t->text('service_terms')->nullable();
            $t->text('exclusions')->nullable();
            $t->text('notes')->nullable();
            $t->string('status', 30)->default('draft');          // see Quotation::STATUSES
            $t->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->string('acceptance_channel', 30)->nullable();
            $t->string('acceptance_token', 64)->nullable()->unique(); // secure public link
            $t->timestamps();
        });
        Schema::create('quotation_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $t->string('label');
            $t->string('kind', 30)->default('charge');           // charge|discount|surcharge
            $t->decimal('amount', 14, 2);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });
        Schema::create('quotation_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('event', 40);                             // created|revised|submitted|approved|rejected|sent|viewed|accepted|declined|expired|handover
            $t->json('meta')->nullable();
            $t->timestamps();
        });
        Schema::create('commercial_handovers', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();               // XER-HND-2026-000001
            $t->foreignId('quotation_id')->unique()->constrained()->cascadeOnDelete(); // ONE handover per quotation
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('inquiry_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->json('snapshot');                                // accepted commercial snapshot
            $t->string('status', 20)->default('pending');        // pending|received|cancelled
            $t->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
            $t->dropConstrainedForeignId('owner_id');
            $t->dropColumn(['type','trading_name','category','whatsapp','preferred_channel','preferred_language','country','city','registration_no','contact_person','status','source','notes','deleted_at']);
        });
        foreach (['commercial_handovers','quotation_events','quotation_items','quotations','rate_cards','cargo_assessments','inquiry_attachments','inquiry_activities','inquiries','lead_activities','leads','customer_documents','customer_addresses','customer_contacts'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
