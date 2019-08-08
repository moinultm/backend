<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProfileRolesTable extends Migration
{

    public function up()
    {
        Schema::create('profile_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('refProfile');
            $table->foreign('refProfile')->references('id')->on('profiles');
            $table->unsignedBigInteger('refRole');
            $table->foreign('refRole')->references('id')->on('roles');
            $table->primary(['refProfile', 'refRole']);
        });
    }

    public function down()
    {
        Schema::table('profile_roles', function (Blueprint $table) {
            $table->dropForeign(['refProfile']);
            $table->dropForeign(['refRole']);
        });
        Schema::dropIfExists('profile_roles');
    }
}
