<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserProfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $user = DB::table('users')
            ->where('email', 'moinultm@gmail.com')
            ->first()
            ->id;
        $profile = DB::table('profiles')
            ->where('code', 'PROFILE_ADMIN')
            ->first()
            ->id;
        DB::table('user_profiles')
            ->insert([
                'refUser' => $user,
                'refProfile' => $profile
            ]);
    }
}
