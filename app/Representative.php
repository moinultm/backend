<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Representative extends Model
{

    public function product(){
        return $this->belongsTo('App\Product');
    }
    public function client()
    {
        return $this->belongsTo('App\Client');
    }

    public function sells() {
        return $this->hasMany('App\Sell', 'rep_user_id', 'rep_user_id');
    }


}
