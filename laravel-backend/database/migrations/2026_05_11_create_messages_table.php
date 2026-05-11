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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ingredient_request_id')->nullable()->constrained('ingredient_requests')->onDelete('cascade');
            $table->text('message');
            $table->enum('type', ['text', 'call_request', 'system'])->default('text');
            $table->string('call_phone_number')->nullable(); // For call coordination
            $table->enum('call_status', ['pending', 'completed', 'missed'])->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['to_user_id', 'is_read']);
            $table->index(['from_user_id', 'to_user_id']);
            $table->index('ingredient_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
