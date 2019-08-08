<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Profile extends Model
{
    public $primaryKey = 'id';
    public $timestamps = true;

    public $fillable = [
        'code',
        'designation'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = 'profiles';
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class,'profile_roles','refProfile', 'refRole');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_profiles', 'refProfile', 'refUser');
    }
}
