<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->foreignId('company_id')->nullable()->after('user_id')->constrained('companies')->nullOnDelete();
            $t->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            $t->unsignedBigInteger('department_id')->nullable()->after('branch_id');
            $t->foreignId('job_position_id')->nullable()->after('department_id')->constrained('job_positions')->nullOnDelete();
            $t->unsignedBigInteger('manager_employee_id')->nullable()->after('job_position_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->dropConstrainedForeignId('company_id');
            $t->dropConstrainedForeignId('job_position_id');
            $t->dropColumn(['branch_id', 'department_id', 'manager_employee_id']);
        });
    }
};
