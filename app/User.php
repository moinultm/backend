<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\UserProfiles;
 
class User extends Authenticatable
{
    use HasApiTokens,Notifiable,UserProfiles;


    protected $fillable = [
        'name', 'email', 'password',
    ];


    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


/*
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
*/
    public function purchases(){
        return $this->hasMany('App\Purchase');
    }

    public function sells(){
        return $this->hasMany('App\Sell');
    }

    public function transactions(){
        return $this->hasMany('App\Transaction');
    }

    public function payments(){
        return $this->hasMany('App\Payment');
    }

    public function returns(){
        return $this->hasMany('App\ReturnTransaction');
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
