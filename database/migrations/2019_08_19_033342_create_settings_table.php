<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('site_name');
            $table->string('slogan')->nullable();
            $table->string('address');
            $table->string('email', 100)->nullable();
            $table->string('phone')->nullable();
            $table->string('owner_name')->nullable();
            $table->text('site_logo')->nullable();
            $table->string('currency_code')->nullable();
            $table->integer('alert_quantity')->nullable();
            $table->boolean('invoice_tax')->default(0);
            $table->double('invoice_tax_rate')->default(0);
            $table->integer('invoice_tax_type')->default(2);
            $table->string('theme')->default("admin-lte-3");
            $table->string('vat_no')->nullable();
            $table->boolean('enable_purchaser')->default(1);
            $table->boolean('enable_customer')->default(1);
            $table->string('pos_invoice_footer_text')->nullable();
            $table->string('dashboard')->default('chart-box');
            $table->boolean('product_tax')->default(0);
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
        Schema::dropIfExists('settings');
    }
}
