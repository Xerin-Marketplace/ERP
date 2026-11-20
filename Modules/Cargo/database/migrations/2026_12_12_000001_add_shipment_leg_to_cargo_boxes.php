<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargo_boxes', function (Blueprint $t) {
            // Air-cargo leg: a closed box may travel on a Shipment/AWB
            // instead of a passenger-carry CargoTrip (spec 12.4).
            $t->foreignId('shipment_id')->nullable()->after('trip_id')
                ->constrained('shipments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cargo_boxes', function (Blueprint $t) {
            $t->dropConstrainedForeignId('shipment_id');
        });
    }
};
