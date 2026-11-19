<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('legal_name');
            $table->string('trading_name')->nullable();
            $table->string('company_type', 30)->default('subsidiary'); // holding|subsidiary|associate|entity
            $table->foreignId('parent_company_id')->nullable()
                  ->constrained('companies')->nullOnDelete();
            $table->string('registration_number')->nullable();
            $table->string('tin')->nullable();          // Tax Identification Number
            $table->string('vat_number')->nullable();
            $table->string('logo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('country')->default('Tanzania');
            $table->string('region')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('physical_address')->nullable();
            $table->string('postal_address')->nullable();
            $table->string('currency', 3)->default('TZS');
            $table->string('timezone')->default('Africa/Dar_es_Salaam');
            $table->date('operational_start_date')->nullable();
            $table->string('status', 20)->default('active'); // active|suspended|archived
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'company_type']);
            $table->index('parent_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
