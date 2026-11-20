<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('code', 30)->unique();
            $t->unsignedInteger('position')->default(0); $t->unsignedTinyInteger('probability')->default(10);
            $t->boolean('active')->default(true); $t->boolean('is_closed')->default(false); $t->timestamps();
        });
        Schema::create('sales_opportunities', function (Blueprint $t) {
            $t->id(); $t->string('reference', 30)->unique();  // XER-OPP-
            $t->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->foreignId('stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete();
            $t->string('name');
            $t->string('service_category', 40)->nullable();
            $t->decimal('expected_volume', 12, 2)->nullable();
            $t->decimal('expected_revenue', 14, 2)->nullable();
            $t->decimal('expected_margin', 14, 2)->nullable();
            $t->unsignedTinyInteger('probability')->default(10);
            $t->date('expected_close')->nullable();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $t->foreignId('contract_id')->nullable();
            $t->string('status', 20)->default('open');       // open|won|lost|on_hold
            $t->string('lost_reason')->nullable();
            $t->string('competitor')->nullable();
            $t->text('next_action')->nullable();
            $t->timestamps();
            $t->index(['branch_id','status']);
        });
        Schema::create('sales_targets', function (Blueprint $t) {
            $t->id();
            $t->string('scope', 20);                         // company|branch|officer|agent
            $t->unsignedBigInteger('scope_id')->nullable();  // branch_id/user_id/partner_id
            $t->string('period_type', 10)->default('monthly'); // monthly|quarterly|annual
            $t->string('period', 10);                        // 2026-01 | 2026-Q1 | 2026
            $t->string('metric', 30)->default('revenue');    // revenue|new_customers|shipments|contracts
            $t->decimal('target', 14, 2);
            $t->string('currency', 3)->default('TZS');
            $t->timestamps();
            $t->unique(['scope','scope_id','period_type','period','metric']);
        });
        Schema::create('customer_segments', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('code', 30)->unique();
            $t->json('rules')->nullable();                   // {min_revenue, min_shipments, types[]}
            $t->boolean('active')->default(true); $t->timestamps();
        });
        Schema::create('customer_acquisitions', function (Blueprint $t) {
            $t->id(); $t->foreignId('customer_id')->unique()->constrained('customers'); // one attribution per customer
            $t->string('channel', 30);                       // direct|website|referral|agent|broker|corporate|social|walk_in|other
            $t->foreignId('campaign_id')->nullable();
            $t->foreignId('partner_id')->nullable();         // referring agent/broker
            $t->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('registered_at')->nullable();
            $t->timestamp('first_quotation_at')->nullable();
            $t->timestamp('first_accepted_at')->nullable();
            $t->timestamp('first_shipment_at')->nullable();
            $t->timestamp('first_revenue_at')->nullable();
            $t->timestamps();
        });
        Schema::create('commercial_contracts', function (Blueprint $t) {
            $t->id(); $t->string('reference', 30)->unique(); // XER-CON-
            $t->string('type', 40);                          // corporate|sme|agent|broker|carrier|special_rate|partnership
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->foreignId('partner_id')->nullable();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->date('start_date')->nullable(); $t->date('expiry_date')->nullable();
            $t->string('renewal_terms')->nullable();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->json('terms')->nullable();                   // pricing/payment/credit/SLA/commission terms
            $t->unsignedInteger('version')->default(1);
            $t->string('signature_evidence')->nullable();    // required for active
            $t->string('status', 25)->default('draft');      // draft|under_review|pending_approval|approved|pending_signature|active|suspended|expired|terminated|renewed
            $t->timestamps();
        });
        Schema::create('contract_versions', function (Blueprint $t) {
            $t->id(); $t->foreignId('contract_id')->constrained('commercial_contracts')->cascadeOnDelete();
            $t->unsignedInteger('version'); $t->json('terms');
            $t->string('status', 20)->default('superseded'); // current|superseded
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['contract_id','version']);
        });
        Schema::create('pricing_approvals', function (Blueprint $t) {
            $t->id(); $t->string('reference', 30)->unique(); // XER-PRA-
            $t->string('entity_type', 30);                   // discount|special_rate|below_minimum|contract_exception|rate_override|credit_terms|low_margin
            $t->string('entity_ref')->nullable();            // quotation/contract ref
            $t->decimal('original_value', 14, 2)->nullable();
            $t->decimal('requested_value', 14, 2);
            $t->text('reason');
            $t->foreignId('requested_by')->constrained('users');
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->string('status', 20)->default('pending');    // pending|approved|rejected
            $t->timestamps();
        });
        Schema::create('commercial_partners', function (Blueprint $t) {
            $t->id(); $t->string('reference', 30)->unique(); // XER-AGT-/XER-BKR-
            $t->string('type', 10);                          // agent|broker
            $t->string('name'); $t->string('legal_name')->nullable();
            $t->string('contact_name')->nullable(); $t->string('phone', 40)->nullable(); $t->string('email')->nullable();
            $t->string('registration_ref')->nullable();
            $t->json('territories')->nullable();
            $t->json('commission_terms')->nullable();
            $t->string('bank_details')->nullable();          // restricted access
            $t->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('contract_id')->nullable()->constrained('commercial_contracts')->nullOnDelete();
            $t->string('status', 25)->default('registered'); // registered|pending_verification|under_review|approved|active|suspended|rejected|terminated
            $t->timestamps();
        });
        Schema::create('commission_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->nullable()->constrained('commercial_partners')->nullOnDelete();
            $t->string('beneficiary_type', 15);              // agent|broker|officer
            $t->unsignedBigInteger('beneficiary_id')->nullable();
            $t->string('basis', 30);                         // revenue_pct|per_shipment|per_acquisition|tiered
            $t->decimal('rate', 8, 4)->nullable();           // e.g. 0.0500 = 5%
            $t->decimal('fixed_amount', 12, 2)->nullable();
            $t->string('currency', 3)->default('TZS');
            $t->date('effective_from')->nullable(); $t->date('effective_to')->nullable();
            $t->boolean('requires_collected')->default(true); // pay only on collected revenue
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });
        Schema::create('commission_records', function (Blueprint $t) {
            $t->id(); $t->string('reference', 30)->unique(); // XER-CMS-
            $t->foreignId('rule_id')->nullable()->constrained('commission_rules')->nullOnDelete();
            $t->string('beneficiary_type', 15);
            $t->unsignedBigInteger('beneficiary_id');
            $t->string('source_type', 20);                   // invoice|quotation|customer|shipment
            $t->unsignedBigInteger('source_id');
            $t->decimal('basis_amount', 14, 2);
            $t->decimal('commission_amount', 14, 2);
            $t->string('currency', 3)->default('TZS');
            $t->json('calc_snapshot')->nullable();
            $t->string('status', 20)->default('calculated'); // calculated|under_review|approved|rejected|payable|paid|reversed|disputed
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('settlement_ref')->nullable();        // Finance payment ref
            $t->timestamps();
            $t->unique(['beneficiary_type','beneficiary_id','source_type','source_id']); // dup prevention
        });
        Schema::create('commercial_campaigns', function (Blueprint $t) {
            $t->id(); $t->string('reference', 30)->unique(); // XER-CAM-
            $t->string('name'); $t->string('type', 30)->default('discount'); // acquisition|discount|seasonal|corporate|referral|agent|service|route
            $t->date('start_date'); $t->date('end_date');
            $t->string('segment_code')->nullable();
            $t->string('discount_type', 15)->nullable();     // percent|fixed
            $t->decimal('discount_value', 12, 2)->nullable();
            $t->decimal('max_discount', 12, 2)->nullable();
            $t->unsignedInteger('usage_limit')->nullable();
            $t->unsignedInteger('used_count')->default(0);
            $t->decimal('budget', 14, 2)->nullable();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('status', 20)->default('draft');      // draft|active|paused|ended|cancelled
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['commercial_campaigns','commission_records','commission_rules','commercial_partners','pricing_approvals','contract_versions','commercial_contracts','customer_acquisitions','customer_segments','sales_targets','sales_opportunities','pipeline_stages'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
