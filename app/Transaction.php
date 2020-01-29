<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use SoftDeletes;

    protected $appends = array( 'total_paid','total_return','total_pay' ,'client_name','user_name','total_quantity_challan','total_quantity_sells','total_quantity_purchases');

    public static  $preventAttrSet = false;


    public function client(){
        return $this->belongsTo('App\Client');
    }

    public function purchases() {
        return $this->hasMany('App\Purchase', 'reference_no', 'reference_no');
    }

    public function challans() {
        return $this->hasMany('App\Representative', 'ref_no', 'reference_no');
    }

    public function gifts() {
        return $this->hasMany('App\GiftProduct', 'reference_no', 'reference_no');
    }

    public function damages() {
        return $this->hasMany('App\DamageProduct', 'reference_no', 'reference_no');
    }


    public function orders() {
        return $this->hasMany('App\Order', 'reference_no', 'reference_no');
    }

    public function orderInvoices() {
        return $this->hasMany('App\Transaction', 'order_no', 'reference_no');
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

    public function user()
    {
        return $this->belongsTo('App\User');
    }




    public function getUserNameAttribute()
    {
        $ret= $this->user()->select('name')->where('id', $this->user_id)->pluck('name');
        return $ret;
    }



    public function getTotalPaidAttribute()
    {
        $ret= $this->payments()->where('type', 'credit')->sum('amount');

        if (self::$preventAttrSet) {return []; }

        return $ret;
    }

    public function getTotalReturnAttribute()
    {
        if (self::$preventAttrSet) {return []; }
        $ret= $this->payments()->where('type', 'return')->sum('amount');
        return $ret;
    }


    public function getTotalPayAttribute()
    {
        if (self::$preventAttrSet) {return []; }
        $return= $this->payments()->where('type', 'return')->sum('amount');
        $paid= $this->payments()->where('type', 'credit')->sum('amount');

        return $paid-$return;
    }



    public function getTotalQuantityChallanAttribute()
    {
        $sum1= $this->challans()->where('ref_no', $this->reference_no)->sum('quantity');
        return $sum1;
    }

    public function getTotalQuantitySellsAttribute()
    {
        $sum1= $this->sells()->where('reference_no', $this->reference_no)->sum('quantity');
        return $sum1;
    }

    public function getTotalQuantityPurchasesAttribute()
    {
        $sum1= $this->purchases()->where('reference_no', $this->reference_no)->sum('quantity');
        return $sum1;
    }



    public function getClientNameAttribute()
    {

        if (self::$preventAttrSet) {return []; }

        $ret= $this->client()->select('full_name')
            ->where('id', $this->client_id)->pluck('full_name');
        return $ret;
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
