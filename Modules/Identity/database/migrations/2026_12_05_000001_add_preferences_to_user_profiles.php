<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $t) {
            $t->string('preferred_language', 10)->default('en')->after('photo_path');
            $t->string('theme', 20)->default('light')->after('preferred_language');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $t) {
            $t->dropColumn(['preferred_language', 'theme']);
        });
    }
};
