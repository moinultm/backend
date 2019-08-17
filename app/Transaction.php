<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public function client(){
        return $this->belongsTo('App\Client');
    }

    public function sells() {
        return $this->hasMany('App\Sell', 'reference_no', 'reference_no');
    }

    public function warehouse () {
        return $this->belongsTo('App\Warehouse');
    }

}
