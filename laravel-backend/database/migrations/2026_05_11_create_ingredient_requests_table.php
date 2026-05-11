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
        Schema::create('ingredient_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karenderia_id')->constrained('karenderias')->onDelete('cascade');
            $table->string('title'); // e.g., "Chicken Breast - 5kg"
            $table->text('description')->nullable();
            $table->string('ingredient_type'); // e.g., "Meat", "Produce", "Dairy"
            $table->decimal('needed_quantity', 10, 2);
            $table->string('unit'); // kg, lbs, pieces, etc.
            $table->date('needed_by_date');
            $table->decimal('budget', 10, 2)->nullable();
            $table->enum('status', ['open', 'accepted', 'cancelled', 'completed'])->default('open');
            $table->foreignId('accepted_supplier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('location_coordinates')->nullable(); // JSON with lat/long for nearby search
            $table->string('delivery_address')->nullable();
            $table->integer('expiry_hours')->default(48); // How long request stays open
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('karenderia_id');
            $table->index('accepted_supplier_id');
            $table->index('needed_by_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_requests');
    }
};
