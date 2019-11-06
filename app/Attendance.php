<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Attendance extends Model
{

    protected $appends = array( 'user_name'  );



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'employee_id');
    }

    public function getUserNameAttribute()
    {
        $ret= $this->user()->select('name')->where('id', $this->employee_id)->pluck('name');
        return $ret;
    }


}
