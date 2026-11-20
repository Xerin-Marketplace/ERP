<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_opportunities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->string('reference')->index();                 // official tender ref
            $t->string('buyer_name');
            $t->string('buyer_type', 30)->default('public');  // public|private|international|ngo
            $t->string('category', 40)->default('goods');     // goods|works|services|consultancy|framework|jv|subcontract
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('location')->nullable();
            $t->string('source', 40)->default('manual');      // nest|portal|newspaper|email|referral|manual
            $t->string('source_url')->nullable();
            $t->string('stage', 30)->default('registered')->index();
            $t->decimal('estimated_value', 18, 2)->nullable();
            $t->decimal('submitted_value', 18, 2)->nullable();
            $t->string('currency', 3)->default('TZS');
            $t->decimal('document_fee', 18, 2)->nullable();
            $t->decimal('security_required', 18, 2)->nullable();
            $t->decimal('performance_security_pct', 5, 2)->nullable();
            $t->date('published_at')->nullable();
            $t->date('prebid_meeting_at')->nullable();
            $t->date('clarification_deadline')->nullable();
            $t->dateTime('submission_deadline')->nullable();
            $t->unsignedInteger('validity_days')->nullable();
            $t->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['reference', 'buyer_name']);
        });

        Schema::create('tender_team_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role', 40); // manager|coordinator|technical|qs|finance|compliance|legal|approver|document_controller
            $t->timestamps();
            $t->unique(['tender_id', 'user_id', 'role']);
        });

        Schema::create('tender_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->nullable()->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('category', 40); // invitation|instructions|boq|specs|addendum|submission|award|contract|qualification
            $t->string('title');
            $t->string('path');
            $t->string('original_name');
            $t->string('mime_type', 100);
            $t->unsignedBigInteger('size');
            $t->unsignedInteger('version')->default(1);
            $t->string('status', 20)->default('draft'); // draft|reviewed|approved|superseded
            $t->foreignId('uploaded_by')->constrained('users');
            $t->timestamps();
        });

        Schema::create('tender_requirements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('section_ref')->nullable();
            $t->text('content');
            $t->string('type', 30)->default('technical'); // eligibility|technical|financial|security|form|deadline
            $t->boolean('mandatory')->default(false);
            $t->string('status', 30)->default('not_reviewed'); // not_reviewed|compliant|partial|non_compliant|clarification
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('response')->nullable();
            $t->timestamps();
        });

        Schema::create('tender_company_qualifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('doc_type', 50); // incorporation|tin|vat|tax_clearance|license|profile|financials|insurance|experience|authorization
            $t->string('doc_number')->nullable();
            $t->string('issuing_authority')->nullable();
            $t->date('issued_at')->nullable();
            $t->date('expires_at')->nullable();
            $t->string('status', 20)->default('valid'); // valid|expired|pending|rejected
            $t->foreignId('document_id')->nullable()->constrained('tender_documents')->nullOnDelete();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('tender_assessments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->json('scores'); // commercial|technical|compliance|strategic|risk => {score, notes}
            $t->unsignedTinyInteger('total_score');
            $t->string('decision', 20)->default('pending'); // bid|no_bid|conditional_bid|pending_review
            $t->text('reason')->nullable();
            $t->foreignId('assessed_by')->constrained('users');
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });

        Schema::create('tender_boqs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->unsignedInteger('version')->default(1);
            $t->string('status', 20)->default('draft'); // draft|review|approved|submitted|locked
            $t->decimal('overhead_pct', 5, 2)->default(0);
            $t->decimal('profit_pct', 5, 2)->default(0);
            $t->decimal('tax_pct', 5, 2)->default(0);
            $t->decimal('contingency_pct', 5, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('tender_boq_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('boq_id')->constrained('tender_boqs')->cascadeOnDelete();
            $t->string('section')->nullable();
            $t->string('item_no');
            $t->text('description');
            $t->string('unit', 20)->default('item');
            $t->decimal('quantity', 18, 4);
            $t->decimal('material_cost', 18, 4)->default(0);
            $t->decimal('labour_cost', 18, 4)->default(0);
            $t->decimal('equipment_cost', 18, 4)->default(0);
            $t->decimal('transport_cost', 18, 4)->default(0);
            $t->decimal('other_cost', 18, 4)->default(0);
            $t->decimal('subcontract_cost', 18, 4)->default(0);
            $t->decimal('unit_rate', 18, 4)->nullable(); // derived unless overridden
            $t->text('original_description')->nullable(); // immutable issuer text
            $t->decimal('original_quantity', 18, 4)->nullable();
            $t->text('remarks')->nullable();
            $t->timestamps();
        });

        Schema::create('tender_quotations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->string('supplier_name');
            $t->decimal('total_price', 18, 2);
            $t->string('currency', 3)->default('TZS');
            $t->unsignedInteger('lead_days')->nullable();
            $t->date('valid_until')->nullable();
            $t->string('payment_terms')->nullable();
            $t->boolean('technically_compliant')->default(true);
            $t->boolean('selected')->default(false);
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('tender_pricing_scenarios', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('name', 40); // conservative|competitive|target|approved
            $t->decimal('direct_cost', 18, 2);
            $t->decimal('overhead', 18, 2)->default(0);
            $t->decimal('profit', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0);
            $t->decimal('price', 18, 2);
            $t->decimal('margin_pct', 6, 2);
            $t->unsignedInteger('version')->default(1);
            $t->string('status', 20)->default('draft'); // draft|approved|superseded
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('tender_securities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('type', 30); // tender_security|performance|advance_payment|retention|warranty
            $t->string('form', 30)->nullable(); // bank_guarantee|insurance_bond|declaration
            $t->string('issuer')->nullable();
            $t->decimal('amount', 18, 2);
            $t->string('currency', 3)->default('TZS');
            $t->date('issued_at')->nullable();
            $t->date('expires_at');
            $t->date('required_until');
            $t->string('status', 20)->default('pending'); // pending|issued|verified|released|expired
            $t->foreignId('document_id')->nullable()->constrained('tender_documents')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('tender_approvals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('step', 40); // bid_decision|technical_review|finance_review|compliance|management|submission_auth
            $t->foreignId('approver_id')->constrained('users');
            $t->string('decision', 20)->default('pending'); // pending|approved|rejected|returned
            $t->text('comment')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });

        Schema::create('tender_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('method', 30); // nest|portal|physical|email
            $t->string('platform_ref')->nullable();
            $t->timestamp('submitted_at');
            $t->foreignId('submitted_by')->constrained('users');
            $t->foreignId('evidence_document_id')->nullable()->constrained('tender_documents')->nullOnDelete();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });

        Schema::create('tender_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('type', 40); // opened|under_eval|clarification|negotiation|intention_to_award|awarded|unsuccessful|cancelled
            $t->text('note')->nullable();
            $t->date('event_date');
            $t->foreignId('recorded_by')->constrained('users');
            $t->timestamps();
        });

        Schema::create('tender_contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('contract_ref');
            $t->decimal('value', 18, 2);
            $t->string('currency', 3)->default('TZS');
            $t->date('signed_at')->nullable();
            $t->date('starts_at')->nullable();
            $t->date('ends_at')->nullable();
            $t->string('status', 20)->default('draft'); // draft|active|completed|terminated|closed
            $t->timestamps();
        });

        Schema::create('tender_contract_milestones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contract_id')->constrained('tender_contracts')->cascadeOnDelete();
            $t->string('title');
            $t->decimal('amount', 18, 2)->nullable();
            $t->date('due_date')->nullable();
            $t->string('status', 20)->default('pending'); // pending|achieved|invoiced|paid
            $t->timestamps();
        });

        Schema::create('tender_transitions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tender_id')->constrained('tender_opportunities')->cascadeOnDelete();
            $t->string('from_stage', 30);
            $t->string('to_stage', 30);
            $t->foreignId('actor_id')->constrained('users');
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['tender_transitions','tender_contract_milestones','tender_contracts','tender_events',
            'tender_submissions','tender_approvals','tender_securities','tender_pricing_scenarios',
            'tender_quotations','tender_boq_items','tender_boqs','tender_assessments',
            'tender_company_qualifications','tender_requirements','tender_documents',
            'tender_team_members','tender_opportunities'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
