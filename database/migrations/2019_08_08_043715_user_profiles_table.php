<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('refUser');
            $table->foreign('refUser')->references('id')->on('users');
            $table->unsignedBigInteger('refProfile');
            $table->foreign('refProfile')->references('id')->on('profiles');
            $table->primary(['refProfile', 'refUser']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['refUser']);
            $table->dropForeign(['refProfile']);
        });
        Schema::dropIfExists('user_profiles');
    }
}
