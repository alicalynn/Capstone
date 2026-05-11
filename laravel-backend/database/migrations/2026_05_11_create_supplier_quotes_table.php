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
        Schema::create('supplier_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_request_id')->constrained('ingredient_requests')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('available_quantity', 10, 2);
            $table->string('unit');
            $table->text('notes')->nullable(); // e.g., "Fresh batch arriving tomorrow", "Premium grade"
            $table->date('delivery_date')->nullable();
            $table->string('delivery_method')->nullable(); // pickup, delivery
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['ingredient_request_id', 'status']);
            $table->index('supplier_id');
            $table->unique(['ingredient_request_id', 'supplier_id']); // One quote per supplier per request
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_quotes');
    }
};
