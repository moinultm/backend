<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Transaction extends Model
{
    use SoftDeletes;

    protected $appends = array('client_name');

    public function client(){
        return $this->belongsTo('App\Client');
    }

    public function sells() {
        return $this->hasMany('App\Sell', 'reference_no', 'reference_no');
    }

    public function warehouse () {
        return $this->belongsTo('App\Warehouse');
    }


    public function getClientNameAttribute()
    {
        $ret=Client::select('full_name')->where('id', $this->client_id)->pluck('full_name')[0];
        return $ret;
    }

}
