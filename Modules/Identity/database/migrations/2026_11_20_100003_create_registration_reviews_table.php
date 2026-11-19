<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 50)->default('note'); // note, verification, assignment, provisioning
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('registration_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_reviews');
    }
};
