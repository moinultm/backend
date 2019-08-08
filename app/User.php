<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
 use App\Traits\UserProfiles;
 
class User extends Authenticatable implements JWTSubject
{
    use Notifiable,UserProfiles;


    protected $fillable = [
        'name', 'email', 'password',
    ];


    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    public function getJWTCustomClaims()
    {
        return [];
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function roles(): array
    {
        $results = collect();
        foreach ($this->profiles as $profile) {
            foreach ($profile->roles as $role) {
                if (!$results->contains($role->code)) {
                    $results->push($role->code);
                }
            }
        }
        return $results->toArray();
    }

}
