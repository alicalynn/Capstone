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
        // Karenderia Reviews/Ratings Table
        Schema::create('karenderia_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('karenderia_id');
            $table->unsignedBigInteger('reviewer_id'); // Supplier/Customer who left review
            $table->string('reviewer_type')->default('supplier'); // supplier, owner, etc.
            
            // Rating and review content
            $table->integer('rating')->comment('1-5 star rating');
            $table->text('comment')->nullable();
            $table->enum('karenderia_status', ['open', 'closed_temporary', 'closed_permanent', 'unknown'])->default('open');
            
            // Moderation
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->text('moderation_note')->nullable();
            
            // Food specific feedback
            $table->text('food_feedback')->nullable();
            $table->integer('food_quality_rating')->nullable()->comment('1-5 scale');
            $table->integer('delivery_experience_rating')->nullable()->comment('1-5 scale');
            
            // Metadata
            $table->json('tags')->nullable()->comment('Quality issues, service, etc');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('karenderia_id')->references('id')->on('karenderias')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('karenderia_id');
            $table->index('reviewer_id');
            $table->index('status');
            $table->index('karenderia_status');
        });

        // Karenderia Issue Reports (for serious issues like allergy mishaps, closure)
        Schema::create('karenderia_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('karenderia_id');
            $table->unsignedBigInteger('reporter_id');
            $table->string('reporter_type')->default('supplier'); // Who reported it
            
            // Report details
            $table->enum('report_type', [
                'permanent_closure',
                'temporary_closure',
                'allergy_issue',
                'food_safety',
                'health_violation',
                'quality_issue',
                'other'
            ]);
            $table->text('description')->comment('Detailed description of the issue');
            $table->text('evidence')->nullable()->comment('Supporting details or proof');
            $table->json('attachments')->nullable()->comment('File URLs or image URLs');
            
            // Status and resolution
            $table->enum('status', ['new', 'under_review', 'acknowledged', 'resolved', 'rejected'])->default('new');
            $table->text('admin_response')->nullable();
            $table->unsignedBigInteger('assigned_admin_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            
            // Impact
            $table->boolean('verified')->default(false);
            $table->integer('similar_reports_count')->default(0)->comment('Count of similar reports from other users');
            
            $table->timestamps();
            
            $table->foreign('karenderia_id')->references('id')->on('karenderias')->onDelete('cascade');
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_admin_id')->references('id')->on('users')->onSetNull()->onDelete('set null');
            $table->index('karenderia_id');
            $table->index('report_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karenderia_reports');
        Schema::dropIfExists('karenderia_reviews');
    }
};
