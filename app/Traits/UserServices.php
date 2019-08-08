<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait UserServices
{

    public function attacheProfilesToUser(int $user, array $profiles)
    {
        DB::table('user_profiles')
            ->where('refUser', $user)
            ->delete();
        foreach ($profiles as $profile) {
            DB::table('user_profiles')
                ->insert([
                    'refUser' => $user,
                    'refProfile' => $profile
                ]);
        }
    }

}