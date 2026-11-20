<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // Spec: department codes are unique within a branch.
            // NULL branch_id rows (company-level departments) are unaffected.
            $table->unique(['branch_id', 'code'], 'departments_branch_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_branch_code_unique');
        });
    }
};
