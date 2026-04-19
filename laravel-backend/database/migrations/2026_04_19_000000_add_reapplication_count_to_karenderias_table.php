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
        Schema::table('karenderias', function (Blueprint $table) {
            $table->integer('reapplication_count')->default(0)->after('rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karenderias', function (Blueprint $table) {
            $table->dropColumn('reapplication_count');
        });
    }
};
