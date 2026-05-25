<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            $table->boolean('owner_confirmed_delivery')->default(false)->after('status');
            $table->timestamp('owner_confirmed_delivery_at')->nullable()->after('owner_confirmed_delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            $table->dropColumn(['owner_confirmed_delivery', 'owner_confirmed_delivery_at']);
        });
    }
};
