<?php

use Illuminate\Database\Seeder;
use App\Role;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {



        \DB::table('roles')->truncate();

        $Role = Role::create(
            array(
                'code' => 'Admin',
                'designation' => 'admin'
            )
        );

    }
}
