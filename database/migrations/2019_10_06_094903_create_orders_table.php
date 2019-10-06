<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sells_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference_no');
            $table->integer('client_id');
            $table->integer('product_id');
            $table->integer('warehouse_id')->default(1);
            $table->double('quantity');
            $table->double('unit_cost_price')->nullable();
            $table->double('product_discount_percentage')->default(0);
            $table->double('sub_total');
            $table->double('product_tax')->nullable();
            $table->timestamp('date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
