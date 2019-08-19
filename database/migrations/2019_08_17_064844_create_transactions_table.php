<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference_no');
            $table->integer('client_id');
            $table->string('transaction_type');
            $table->integer('warehouse_id')->default(1);
            $table->double('discount')->default(0);
            $table->double('total');
            $table->double('labor_cost')->default(0);
            $table->double('paid');
            $table->boolean('return')->default(0);
            $table->double('total_cost_price')->nullable();
            $table->double('invoice_tax')->nullable();
            $table->double('total_tax')->nullable();
            $table->double('net_total')->nullable();
            $table->float('change_amount')->nullable();
            $table->boolean('pos')->default(0);
            $table->timestamp('date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
