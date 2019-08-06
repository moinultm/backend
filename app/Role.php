<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $primaryKey = 'id';
    public $timestamps = true;

    public $fillable = [
        'code',
        'designation'
    ];

}
