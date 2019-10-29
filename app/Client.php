<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Client extends Model
{
    use SoftDeletes;

    protected $fillable = ['id'];

    protected $appends = array('net_total','total_return','total_dues');


    public function purchases(){
        return $this->hasMany('App\Purchase');
    }

    public function sells(){
        return $this->hasMany('App\Sell');
    }

    public function transactions(){
        return $this->hasMany('App\Transaction');
    }

    public function payments(){
        return $this->hasMany('App\Payment');
    }

    public function returns(){
        return $this->hasMany('App\ReturnTransaction');
    }

    //Attrebiutes
    public function getNetTotalAttribute()
    {
        $net_total = $this->transactions()->sum('net_total') + $this->returns()->sum('return_amount') - $this->previous_due;
        return $net_total;
    }


    public function getTotalDuesAttribute()
    {

        $total_return =  $this->payments()->where('type', 'return')->sum('amount');
        $total_received =$this->payments()->where('type', '!=','return')->sum('amount') - $total_return;
      return  $total_due = $this->transactions()->sum('net_total') - ($total_received);
    }
    public function getTotalReturnAttribute()
    {
          $total_return = $this->payments()->where('type', 'return')->sum('amount');
         return $total_return;
    }

}
