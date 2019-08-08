<?php

namespace App\Traits;

use App\Profile;

trait UserProfiles
{


    public function profiles()
    {
        return $this->belongsToMany(Profile::class, 'user_profiles', 'refUser', 'refProfile');
    }

}
