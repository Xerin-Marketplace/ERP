<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('managed_file_id')->constrained('managed_files')->cascadeOnDelete();
            $table->string('document_type', 50)->default('attachment'); // employment_id, supporting, other
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['registration_application_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_documents');
    }
};
