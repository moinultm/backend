<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sell extends Model
{
    use SoftDeletes;

    protected $appends = array( 'client_name','product_name','mrp' );

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

    public function getProductNameAttribute()
    {
        $ret= $this->product()->select('name')->where('id', $this->product_id)->pluck('name')[0];
        return $ret;
    }
    public function getMRPAttribute()
    {
        $ret= $this->product()->select('mrp')->where('id', $this->product_id)->pluck('mrp')[0];
        return $ret;
    }

}
