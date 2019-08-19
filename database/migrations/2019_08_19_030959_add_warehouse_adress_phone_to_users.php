<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWarehouseAdressPhoneToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('warehouse_id')->after('password')->default(1);
            $table->boolean('inactive')->after('email')->default(0);
            $table->string('address')->nullable()->after('remember_token');
            $table->string('phone')->nullable()->after('address');
            $table->text('image')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->dropColumn('phone');
            $table->dropColumn('image');
                $table->dropColumn('warehouse_id');
                $table->dropColumn('inactive');

        });
    }
}
