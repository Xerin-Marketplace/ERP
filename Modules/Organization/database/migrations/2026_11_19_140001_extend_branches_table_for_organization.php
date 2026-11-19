<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')
                  ->constrained('companies')->nullOnDelete();
            $table->string('branch_type', 30)->default('branch')->after('name');
            $table->unsignedBigInteger('manager_id')->nullable()->after('branch_type'); // future Employee FK
            $table->string('contact_person')->nullable()->after('manager_id');
            $table->string('phone')->nullable()->after('contact_person');
            $table->string('email')->nullable()->after('phone');
            $table->string('country')->default('Tanzania')->after('city');
            $table->string('region')->nullable()->after('country');
            $table->string('district')->nullable()->after('region');
            $table->string('physical_address')->nullable()->after('district');
            $table->string('postal_address')->nullable()->after('physical_address');
            $table->decimal('latitude', 10, 7)->nullable()->after('postal_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->date('opening_date')->nullable()->after('longitude');
            $table->text('description')->nullable()->after('opening_date');
            $table->string('status', 20)->default('active')->after('description');
            $table->foreignId('created_by')->nullable()->after('status')
                  ->constrained('users')->nullOnDelete();
        });

        // Migrate is_active → status
        DB::table('branches')->where('is_active', false)->update(['status' => 'suspended']);

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->index(['status', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('city');
        });
        DB::table('branches')->where('status', 'suspended')->update(['is_active' => false]);
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['status', 'company_id']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn([
                'branch_type', 'manager_id', 'contact_person', 'phone', 'email',
                'country', 'region', 'district', 'physical_address', 'postal_address',
                'latitude', 'longitude', 'opening_date', 'description', 'status',
            ]);
        });
    }
};
