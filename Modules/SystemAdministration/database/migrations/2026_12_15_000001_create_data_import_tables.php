<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master-file imports (spec data migration): staged uploads with
        // column mapping, alias normalization and per-row error report.
        Schema::create('data_imports', function (Blueprint $t) {
            $t->id();
            $t->string('entity', 30);                    // customers|suppliers
            $t->foreignId('file_id')->constrained('managed_files')->cascadeOnDelete();
            $t->string('original_name');
            $t->json('mapping')->nullable();             // source column => target field
            $t->string('status', 20)->default('uploaded'); // uploaded|previewed|imported|failed
            $t->unsignedInteger('total_rows')->default(0);
            $t->unsignedInteger('imported_count')->default(0);
            $t->unsignedInteger('skipped_count')->default(0);
            $t->unsignedInteger('error_count')->default(0);
            $t->json('errors')->nullable();              // [{row, message}]
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // Spelling-variant mappings: raw cell value => canonical value.
        Schema::create('data_import_aliases', function (Blueprint $t) {
            $t->id();
            $t->string('entity', 30);
            $t->string('field', 40);                     // category|type|city|country…
            $t->string('alias');                         // stored lowercased+trimmed
            $t->string('canonical');
            $t->timestamps();
            $t->unique(['entity', 'field', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_import_aliases');
        Schema::dropIfExists('data_imports');
    }
};
