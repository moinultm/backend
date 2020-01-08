<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Representative extends Model
{
    use SoftDeletes;

 protected  $table='representatives_stock';



    public $fillable = [
        'user_id',
        'date',
        'product_id' ,
        'quantity'
    ];

    public function product(){
        return $this->belongsTo('App\Product');
    }
    public function user()
    {
        return $this->belongsTo('App\User');
    }



}
