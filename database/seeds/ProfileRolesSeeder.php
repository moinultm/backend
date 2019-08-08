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
        $roleRoles = DB::table('roles')
        ->where('code', 'ROLE_ROLES')
        ->first()
        ->id;
    $roleProfiles = DB::table('roles')
        ->where('code', 'ROLE_PROFILES')
        ->first()
        ->id;
    $roleUsers = DB::table('roles')
        ->where('code', 'ROLE_USERS')
        ->first()
        ->id;
    $profile = DB::table('profiles')
        ->where('code', 'PROFILE_ADMIN')
        ->first()
        ->id;
    DB::table('profile_roles')
        ->insert([
            [
                'refRole' => $roleRoles,
                'refProfile' => $profile
            ], [
                'refRole' => $roleProfiles,
                'refProfile' => $profile
            ], [
                'refRole' => $roleUsers,
                'refProfile' => $profile
            ]
        ]);
    }
}
