<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });
        Schema::table('tickets', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->after('id')->constrained('customers')->nullOnDelete();
            $t->string('source', 20)->default('internal')->after('customer_id'); // internal|customer_portal
            $t->json('related')->nullable()->after('custom_fields');            // {shipment_id, package_id, order_id, invoice_id}
        });
        Schema::create('complaints', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-CMP-
            $t->foreignId('customer_id')->constrained('customers');
            $t->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $t->foreignId('package_id')->nullable()->constrained('cargo_packages')->nullOnDelete();
            $t->foreignId('order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $t->string('category', 40);                      // cargo_delay|cargo_damage|missing_cargo|incorrect_cargo|delivery_failure|staff_conduct|customer_service|billing_dispute|communication_failure|other
            $t->text('description');
            $t->string('evidence')->nullable();
            $t->string('severity', 10)->default('medium');
            $t->string('status', 30)->default('submitted');  // submitted|acknowledged|under_investigation|awaiting_evidence|awaiting_department|resolution_proposed|resolved|rejected|reopened|closed
            $t->foreignId('investigator_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('resolution')->nullable();
            $t->timestamps();
        });
        Schema::create('complaint_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $t->string('event', 50);
            $t->string('visibility', 10)->default('internal'); // internal|customer
            $t->text('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('cargo_claims', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-CLM-
            $t->foreignId('customer_id')->constrained('customers');
            $t->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $t->foreignId('package_id')->nullable()->constrained('cargo_packages')->nullOnDelete();
            $t->foreignId('order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $t->string('awb')->nullable();
            $t->string('type', 30);                          // lost|damaged|missing_items|partial_delivery|incorrect_delivery|other
            $t->text('description');
            $t->date('incident_date')->nullable();
            $t->decimal('declared_value', 14, 2)->nullable();
            $t->decimal('claimed_amount', 14, 2);
            $t->string('currency', 3)->default('TZS');
            $t->decimal('approved_amount', 14, 2)->nullable();
            $t->string('status', 30)->default('submitted');  // submitted|under_review|investigation|additional_evidence|pending_approval|approved|partially_approved|rejected|settlement_pending|settled|closed|reopened
            $t->foreignId('investigator_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('investigation_notes')->nullable();
            $t->foreignId('decision_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decision_at')->nullable();
            $t->text('decision_reason')->nullable();
            $t->string('settlement_ref')->nullable();       // Finance payment/credit ref — recorded, never auto-posted
            $t->timestamps();
        });
        Schema::create('claim_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('claim_id')->constrained('cargo_claims')->cascadeOnDelete();
            $t->string('event', 50);
            $t->string('evidence')->nullable();
            $t->text('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('customer_feedback', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('customers');
            $t->string('survey_key', 50);                    // e.g. delivery:{order_id} — dedupes
            $t->unsignedTinyInteger('rating');               // 1-5
            $t->string('category', 30)->default('delivery');
            $t->text('comment')->nullable();
            $t->foreignId('order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $t->string('status', 20)->default('published');
            $t->text('response')->nullable();
            $t->timestamps();
            $t->unique(['customer_id', 'survey_key']);
        });
        Schema::create('communication_preferences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->unique()->constrained('customers');
            $t->json('channels')->nullable();                // {whatsapp:true,sms:true,email:true,in_app:true}
            $t->string('language', 5)->default('en');
            $t->boolean('operational')->default(true);
            $t->boolean('marketing_consent')->default(false);
            $t->timestamps();
        });
        Schema::create('customer_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('customers');
            $t->string('channel', 15)->default('in_app');    // whatsapp|sms|email|in_app
            $t->string('direction', 10)->default('out');     // in|out
            $t->string('subject')->nullable();
            $t->text('body');
            $t->string('related_type', 30)->nullable();      // ticket|complaint|claim|shipment|order
            $t->unsignedBigInteger('related_id')->nullable();
            $t->string('status', 20)->default('queued');     // queued|sent|delivered|failed — never claim delivered prematurely
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['customer_id', 'related_type', 'related_id']);
        });
        Schema::create('privacy_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();          // XER-PRV-
            $t->foreignId('customer_id')->constrained('customers');
            $t->string('type', 20);                          // access|correction|export|closure|preferences
            $t->text('details')->nullable();
            $t->string('status', 20)->default('submitted');  // submitted|in_review|approved|completed|rejected
            $t->text('resolution')->nullable();
            $t->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
        Schema::dropIfExists('customer_messages');
        Schema::dropIfExists('communication_preferences');
        Schema::dropIfExists('customer_feedback');
        Schema::dropIfExists('claim_events');
        Schema::dropIfExists('cargo_claims');
        Schema::dropIfExists('complaint_events');
        Schema::dropIfExists('complaints');
        Schema::table('tickets', fn (Blueprint $t) => $t->dropConstrainedForeignId('customer_id')->dropColumn(['source','related']));
        Schema::table('customers', fn (Blueprint $t) => $t->dropConstrainedForeignId('user_id'));
    }
};
