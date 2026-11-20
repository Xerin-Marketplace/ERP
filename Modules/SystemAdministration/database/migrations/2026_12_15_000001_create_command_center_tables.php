<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_command_executions', function (Blueprint $t) {
            $t->id();
            $t->string('command_id');
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->json('params')->nullable();
            $t->string('risk', 10);
            $t->string('status', 20)->default('pending_approval'); // pending_approval|running|completed|failed|cancelled|rejected
            $t->longText('output')->nullable();
            $t->text('error')->nullable();
            $t->unsignedInteger('duration_ms')->nullable();
            $t->string('reason')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('correlation_id', 40)->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
            $t->index(['command_id', 'status']);
        });

        Schema::create('system_command_approvals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('execution_id')->constrained('system_command_executions')->cascadeOnDelete();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('decision', 10)->default('pending'); // pending|approved|rejected
            $t->string('comment')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('system_command_approvals');
        Schema::dropIfExists('system_command_executions');
    }
};
