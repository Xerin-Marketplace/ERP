<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_team_members', function (Blueprint $t) {
            $t->string('availability', 20)->default('available')->after('employee_id'); // available|busy|on_break|offline|on_leave
            $t->timestamp('availability_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_team_members', function (Blueprint $t) {
            $t->dropColumn(['availability', 'availability_updated_at']);
        });
    }
};
