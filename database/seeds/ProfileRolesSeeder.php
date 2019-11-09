<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roleAdmin= DB::table('roles')
        ->where('code', 'ROLE_ADMIN_PRIVILEGE')
        ->first()
        ->id;
    $roleOwner = DB::table('roles')
        ->where('code', 'ROLE_OWNER_PRIVILEGE')
        ->first()
        ->id;

        $roleManager = DB::table('roles')
            ->where('code', 'ROLE_MANAGER_PRIVILEGE')
            ->first()
            ->id;



    $profile = DB::table('profiles')
        ->where('code', 'PROFILE_ADMIN')
        ->first()
        ->id;
    DB::table('profile_roles')
        ->insert([
            [
                'refRole' => $roleAdmin,
                'refProfile' => $profile
            ], [
                'refRole' => $roleOwner,
                'refProfile' => $profile
            ], [
                'refRole' => $roleManager,
                'refProfile' => $profile
            ]
        ]);
    }
}
