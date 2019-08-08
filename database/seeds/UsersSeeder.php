<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('users')
            ->insert([
                'email' => 'moinultm@gmail.com',
                'name' => 'Mainul Islam',
                'password' => bcrypt('moinultm'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
    }
}
