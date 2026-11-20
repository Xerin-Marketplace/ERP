<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_invoices', function (Blueprint $t) {
            $t->foreignId('package_id')->nullable()->after('customer_id')
                ->constrained('cargo_packages')->nullOnDelete();
            $t->index('package_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_invoices', function (Blueprint $t) {
            $t->dropConstrainedForeignId('package_id');
        });
    }
};
