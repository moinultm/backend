<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
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
        $this->table = 'roles';
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class,  'profile_roles', 'refRole', 'refProfile');
    }
}
