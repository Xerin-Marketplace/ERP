<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_administrators', function (Blueprint $t) {
            $t->json('delegable_roles')->nullable()->after('assigned_by');      // role names the BA may assign
            $t->json('delegable_permissions')->nullable()->after('delegable_roles');
            $t->string('status', 20)->default('active')->after('delegable_permissions'); // active|suspended
            $t->timestamp('expires_at')->nullable()->after('status');
            $t->timestamp('suspended_at')->nullable()->after('expires_at');
            $t->unsignedBigInteger('suspended_by')->nullable()->after('suspended_at');
        });
    }
    public function down(): void
    {
        Schema::table('branch_administrators', fn (Blueprint $t) =>
            $t->dropColumn(['delegable_roles','delegable_permissions','status','expires_at','suspended_at','suspended_by']));
    }
};
