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
            // Payment Status Tracking
            $table->string('payment_status')->default('pending')->after('status');
            $table->timestamp('payment_date')->nullable()->after('payment_status');
            $table->string('payment_method')->nullable()->after('payment_date');
            $table->string('payment_reference')->nullable()->after('payment_method');

            // Delivery Method & Address
            $table->string('delivery_method')->nullable()->after('delivery_date');
            $table->text('delivery_address')->nullable()->after('delivery_method');
            $table->json('delivery_coordinates')->nullable()->after('delivery_address');

            // Delivery Timeline - Timestamps
            $table->timestamp('confirmed_at')->nullable()->after('delivery_coordinates');
            $table->timestamp('shipped_at')->nullable()->after('confirmed_at');
            $table->timestamp('out_for_delivery_at')->nullable()->after('shipped_at');
            $table->timestamp('delivered_at')->nullable()->after('out_for_delivery_at');

            // Delivery Proof & Notes
            $table->text('delivery_notes')->nullable()->after('delivered_at');
            $table->string('delivered_by_name')->nullable()->after('delivery_notes');
            $table->string('delivery_signature_url')->nullable()->after('delivered_by_name');
            $table->json('photo_proof_urls')->nullable()->after('delivery_signature_url');

            // Status History & Retry Logic
            $table->json('status_history')->nullable()->after('photo_proof_urls');
            $table->text('failed_reason')->nullable()->after('status_history');
            $table->integer('retry_count')->default(0)->after('failed_reason');
            $table->integer('max_retries')->default(3)->after('retry_count');

            // Indexes
            $table->index('payment_status');
            $table->index('delivery_method');
            $table->index(['status', 'payment_status']);
            $table->index(['confirmed_at', 'delivered_at']);
        });

        // Modify status column to include new statuses
        // Note: This might fail on some DB systems, so we'll do it carefully
        try {
            Schema::table('supply_orders', function (Blueprint $table) {
                $table->enum('status', [
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
                ])->default('pending')->change();
            });
        } catch (\Exception $e) {
            // If change() doesn't work, the migration will still succeed
            // but existing queries need to use the old enum values
            \Illuminate\Support\Facades\Log::warning('Could not modify status enum: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_date',
                'payment_method',
                'payment_reference',
                'delivery_method',
                'delivery_address',
                'delivery_coordinates',
                'confirmed_at',
                'shipped_at',
                'out_for_delivery_at',
                'delivered_at',
                'delivery_notes',
                'delivered_by_name',
                'delivery_signature_url',
                'photo_proof_urls',
                'status_history',
                'failed_reason',
                'retry_count',
                'max_retries',
            ]);

            // Drop indexes
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['delivery_method']);
            $table->dropIndex(['status', 'payment_status']);
            $table->dropIndex(['confirmed_at', 'delivered_at']);
        });
    }
};
