<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('code', 30);
            // head_office|regional_office|branch_office|warehouse|site|other
            $table->string('location_type', 30)->default('other');
            $table->string('country')->default('Tanzania');
            $table->string('region')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('physical_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
