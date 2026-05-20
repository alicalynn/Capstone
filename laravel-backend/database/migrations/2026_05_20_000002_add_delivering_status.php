<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'delivering' to the status enum
        // Note: For MySQL, we need to modify the enum by recreating it
        Schema::table('supply_orders', function (Blueprint $table) {
            // Change the status enum to include 'delivering'
            DB::statement("ALTER TABLE supply_orders MODIFY status ENUM(
                'pending',
                'confirmed',
                'payment_confirmed',
                'preparing',
                'shipped',
                'in_transit',
                'out_for_delivery',
                'delivering',
                'delivered',
                'delivery_failed',
                'cancelled'
            ) DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            // Revert to previous enum without 'delivering'
            DB::statement("ALTER TABLE supply_orders MODIFY status ENUM(
                'pending',
                'confirmed',
                'payment_confirmed',
                'preparing',
                'shipped',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'delivery_failed',
                'cancelled'
            ) DEFAULT 'pending'");
        });
    }
};
