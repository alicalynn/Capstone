<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('daily_menus') && Schema::hasColumn('daily_menus', 'special_price')) {
            Schema::table('daily_menus', function (Blueprint $table) {
                $table->dropColumn('special_price');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('daily_menus') && !Schema::hasColumn('daily_menus', 'special_price')) {
            Schema::table('daily_menus', function (Blueprint $table) {
                $table->decimal('special_price', 8, 2)->nullable()->after('is_available');
            });
        }
    }
};
