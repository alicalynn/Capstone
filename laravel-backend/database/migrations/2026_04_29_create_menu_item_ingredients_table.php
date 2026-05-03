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
        Schema::create('menu_item_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained('inventory')->onDelete('cascade');
            $table->decimal('quantity_needed', 8, 3); // Amount of ingredient per serving/unit
            $table->timestamps();

            // Unique constraint: each menu item can have each inventory item only once
            $table->unique(['menu_item_id', 'inventory_id']);
            $table->index(['menu_item_id']);
            $table->index(['inventory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_item_ingredients');
    }
};
