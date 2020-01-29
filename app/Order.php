<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'sells_orders';

    protected $appends = array( 'product_name','mrp' );

    public function product(){
        return $this->belongsTo('App\Product');
    }

    public function client()
    {
        return $this->belongsTo('App\Client');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /*
    public function sells()
    {
        return $this->belongsTo('App\Sell');
    }*/

    public function getProductNameAttribute()
    {
        $ret= $this->product()->select('name')->where('id', $this->product_id)->pluck('name');
        return $ret;
    }

    public function getMRPAttribute()
    {
        $ret= $this->product()->select('mrp')->where('id', $this->product_id)->pluck('mrp');
        return $ret;

    }


}

