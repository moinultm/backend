<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpiresMfgBatchToConsumableProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consumable_products', function (Blueprint $table) {
            $table->string('batch_no')->after('quantity')->nullable();
            $table->string('lot_no')->after('batch_no')->nullable();
            $table->string('pack_size')->after('lot_no')->nullable();
            $table->string('mfg_date')->after('pack_size')->nullable();
            $table->string('exp_date')->after('mfg_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consumable_products', function (Blueprint $table) {
            $table->dropColumn('batch_no');
            $table->dropColumn('lot_no');
            $table->dropColumn('pack_size');
            $table->dropColumn('mfg_date');
            $table->dropColumn('exp_date');
        });
    }
}
