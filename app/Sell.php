<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sell extends Model
{
    use SoftDeletes;

    protected $appends = array('client_name','product_name','product_mrp');
    public function product(){
        return $this->belongsTo('App\Product');
    }

    public function client()
    {
        return $this->belongsTo('App\Client');
    }



    public function getClientNameAttribute()
    {
        $ret=Client::where('id', $this->id);
        return $ret;
    }


    public function getProductNameAttribute()
    {
        $ret=Product::select('name')->where('id', $this->client_id)->pluck('name')[0];
        return $ret;
    }

    public function getProductMrpAttribute()
    {
        $ret=Product::select('mrp')->where('id', $this->client_id)->pluck('mrp')[0];
        return $ret;
    }

}
