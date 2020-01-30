<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sell extends Model
{
    use SoftDeletes;

     //skipped for reporting
  protected $appends = array( 'client_name','product_name','minimum_retail', 'user_name' );


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



    public function getUserNameAttribute()
    {
        $ret= $this->user()->select('name')->where('id', $this->id)->get();
        return $ret;
    }

    public function getClientNameAttribute()
    {
        $ret= $this->client()->select('full_name')->where('id', $this->id)->get();
        return $ret;
    }

    public function getProductNameAttribute()
    {
        $ret= $this->product()->select('name')->where('id', $this->product_id)->pluck('name');
        return $ret;
    }
    public function getMinimumRetailAttribute()
    {
            $ret= $this->product()->select('mrp')->where('id', $this->product_id)->pluck('mrp');
            return $ret;

    }

    // we have eleted the [0] from mrp and name duto  undifiend offset error

}
