<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         DB::table('profiles')
            ->insert([[
                'code' => 'PROFILE_ADMIN',
                'designation' => 'Administrator privileges',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
                [
                    'code' => 'PROFILE_OWNER',
                    'designation' => 'Super-Admin privileges',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'PROFILE_MANAGER',
                    'designation' => 'Manager privileges',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                'code' => 'PROFILE_USER',
                'designation' => 'User privileges',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],

                ]);
    }
}
