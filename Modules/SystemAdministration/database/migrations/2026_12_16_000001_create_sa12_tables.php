<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_setting_scopes', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->string('scope_type', 10);      // group|company|branch|user
            $t->unsignedBigInteger('scope_id');
            $t->text('value')->nullable();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['key', 'scope_type', 'scope_id']);
            $t->index(['scope_type', 'scope_id']);
        });

        Schema::create('system_setting_change_requests', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->string('scope_type', 10)->default('global');
            $t->unsignedBigInteger('scope_id')->nullable();
            $t->text('old_value')->nullable();
            $t->text('new_value')->nullable();
            $t->string('risk', 10);
            $t->string('status', 20)->default('pending'); // pending|approved|rejected|published|stale|withdrawn
            $t->unsignedInteger('base_version')->nullable(); // optimistic lock: version count at submit
            $t->foreignId('requester_id')->constrained('users');
            $t->text('reason');
            $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('approver_comment')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('integration_test_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('integration_id')->constrained('system_integrations')->cascadeOnDelete();
            $t->string('status', 30); // successful|auth_failed|unavailable|timeout|config_invalid|not_tested
            $t->unsignedInteger('duration_ms')->nullable();
            $t->string('error_code')->nullable();
            $t->string('message')->nullable();
            $t->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('user_mfa', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->text('secret');              // encrypted TOTP secret
            $t->json('recovery_hashes')->nullable();
            $t->boolean('enabled')->default(false);
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['user_mfa','integration_test_results','system_setting_change_requests','system_setting_scopes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
