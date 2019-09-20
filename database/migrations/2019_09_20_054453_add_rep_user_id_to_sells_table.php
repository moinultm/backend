<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRepUserIdToSellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->integer('rep_user_id')->after('date')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->dropColumn('rep_user_id');

        });
    }
}
