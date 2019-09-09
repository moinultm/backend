<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sell extends Model
{
    use SoftDeletes;

    protected $appends = array( 'client_name' );

    public function product(){
        return $this->belongsTo('App\Product');
    }

    public function client()
    {
        return $this->belongsTo('App\Client');
    }


    public function getClientNameAttribute()
    {
        $ret= $this->client()->select('full_name')->where('id', $this->id)->get();
        return $ret;
    }


}
