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
        Schema::create('karenderia_supplier_sukis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karenderia_id')->constrained('karenderias')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['karenderia_id', 'supplier_id']);
            $table->index(['supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karenderia_supplier_sukis');
    }
};
