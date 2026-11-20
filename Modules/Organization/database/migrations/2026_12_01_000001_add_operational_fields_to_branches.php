<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('country');
            $table->string('currency', 3)->nullable()->after('country_code');
            $table->string('timezone', 60)->nullable()->after('currency');
            $table->string('postal_code', 20)->nullable()->after('postal_address');
            $table->timestamp('activated_at')->nullable()->after('status');
            $table->timestamp('deactivated_at')->nullable()->after('activated_at');
            $table->foreignId('updated_by')->nullable()->after('created_by')
                  ->constrained('users')->nullOnDelete();

            $table->index(['country_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['country_code', 'status']);
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'country_code', 'currency', 'timezone', 'postal_code',
                'activated_at', 'deactivated_at',
            ]);
        });
    }
};
