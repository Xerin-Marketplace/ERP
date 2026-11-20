<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Encrypted values are longer than the plaintext — widen before the
        // model's `encrypted` cast starts writing ciphertext.
        Schema::table('employee_personal_details', function (Blueprint $t) {
            $t->text('national_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employee_personal_details', function (Blueprint $t) {
            $t->string('national_id', 60)->nullable()->change();
        });
    }
};
