<?php

namespace App;
use Illuminate\Database\Eloquent\Model;


class Representative extends Model
{

 protected  $table='representatives_stock';

    public $fillable = [
        'user_id',
        'date',  'product_id' , 'quantity'
    ];

    public function product(){
        return $this->belongsTo('App\Product');
    }
    public function client()
    {
        return $this->belongsTo('App\Client');
    }



}
