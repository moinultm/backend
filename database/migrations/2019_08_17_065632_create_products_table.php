<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('code');
            $table->integer('category_id');
            $table->integer('subcategory_id')->nullable();
            $table->float('quantity')->nullable();
            $table->longtext('details')->nullable();
            $table->float('cost_price');
            $table->float('mrp');
            $table->integer('tax_id')->nullable();
            $table->float('minimum_retail_price')->nullable();
            $table->string('unit', 11)->nullable();
            $table->boolean('status')->nullable();
            $table->text('image')->nullable();
            $table->double('opening_stock')->nullable();
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
        Schema::dropIfExists('products');
    }
}
