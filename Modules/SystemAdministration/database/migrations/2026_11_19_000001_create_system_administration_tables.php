<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_setting_versions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('group')->default('system')->index();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('risk')->default('low'); // low|medium|high|critical
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('system_branding_assets', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();          // e.g. logo_primary, favicon
            $table->string('path');                  // public-relative path
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->boolean('is_current')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['key', 'is_current']);
        });

        Schema::create('system_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->json('scope')->nullable();       // {roles:[], companies:[], users:[]}
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('system_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('level')->default('info'); // info|warning|danger
            $table->string('audience')->default('all'); // all|admins
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_ack')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('system_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('driver')->default('custom'); // smtp|sms|payment|storage|custom
            $table->boolean('is_enabled')->default(false);
            $table->text('config')->nullable();      // encrypted JSON — secrets never plaintext
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_status')->nullable(); // ok|fail|untested
            $table->string('last_error')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('system_navigation_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('item_key')->unique();    // module:label fingerprint
            $table->string('label_override')->nullable();
            $table->unsignedSmallInteger('order_override')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_navigation_overrides');
        Schema::dropIfExists('system_integrations');
        Schema::dropIfExists('system_announcements');
        Schema::dropIfExists('system_feature_flags');
        Schema::dropIfExists('system_branding_assets');
        Schema::dropIfExists('system_setting_versions');
    }
};
