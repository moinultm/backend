<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoicedQtyToSellsOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sells_orders', function (Blueprint $table) {
            $table->string('invoiced_qty')->after('quantity')->default('0');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sells_orders', function (Blueprint $table) {
            Schema::dropIfExists('invoiced_qty');

        });
    }
}
