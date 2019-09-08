<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Transaction extends Model
{
    use SoftDeletes;

    protected $appends = array( 'total_paid','total_return','total_pay'  );

    public function client(){
        return $this->belongsTo('App\Client');
    }

    public function purchases() {
        return $this->hasMany('App\Purchase', 'reference_no', 'reference_no');
    }

    public function sells() {
        return $this->hasMany('App\Sell', 'reference_no', 'reference_no');
    }

    public function payments() {
        return $this->hasMany('App\Payment', 'reference_no', 'reference_no');
    }

    public function returnSales() {
        return $this->hasMany('App\ReturnTransaction', 'sells_reference_no', 'reference_no');
    }

    public function warehouse () {
        return $this->belongsTo('App\Warehouse');
    }





    public function getTotalPaidAttribute()
    {
       $ret= $this->payments()->where('type', 'credit')->sum('amount');
        return $ret;
    }

    public function getTotalReturnAttribute()
    {
        $ret= $this->payments()->where('type', 'return')->sum('amount');
        return $ret;
    }


    public function getTotalPayAttribute()
    {
        $return= $this->payments()->where('type', 'return')->sum('amount');
        $paid= $this->payments()->where('type', 'credit')->sum('amount');

        return $paid-$return;
    }


    /*
        protected static function boot () {
            parent::boot();
            self::saving(function ($model) {
                $model->warehouse_id = auth()->user()->warehouse_id;
            });
        }

    */

}
