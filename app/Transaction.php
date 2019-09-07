<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Transaction extends Model
{
    use SoftDeletes;

    protected $appends = array('client_name' );

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



    public function getClientNameAttribute()
    {
        $ret=Client::select('full_name')->where('id', $this->client_id)->pluck('full_name')[0];
        return $ret;
    }



    private function getDateValue() {
        return date('m/d/Y', strtotime($this->attributes['date']));
    }

    private function setDateValue($value) {
        $date_parts = explode('/', $value);
        $this->attributes['date'] = $date_parts[2].'-'.$date_parts[0].'-'.$date_parts[1];
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
