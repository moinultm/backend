<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRecivingToRepresentativesStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('representatives_stock', function (Blueprint $table) {
            $table->integer('receiving')->after('date')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('representatives_stock', function (Blueprint $table) {
            $table->text('receiving')->after('date');

        });
    }
}
