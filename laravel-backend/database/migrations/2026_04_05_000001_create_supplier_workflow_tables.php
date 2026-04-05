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
        Schema::create('supplier_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('unit');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('available_stock', 10, 3);
            $table->decimal('minimum_order_quantity', 10, 3)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['supplier_id', 'is_active']);
            $table->index(['category', 'is_active']);
        });

        Schema::create('supply_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karenderia_id')->constrained('karenderias')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'confirmed', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->date('delivery_date')->nullable();
            $table->timestamps();

            $table->index(['karenderia_id', 'status']);
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('supply_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_order_id')->constrained('supply_orders')->onDelete('cascade');
            $table->foreignId('supplier_inventory_item_id')->constrained('supplier_inventory_items')->onDelete('restrict');
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index(['supply_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_order_items');
        Schema::dropIfExists('supply_orders');
        Schema::dropIfExists('supplier_inventory_items');
    }
};
