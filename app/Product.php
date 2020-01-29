<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $appends = array('total_quantity_transaction','sum_opening','total_sells','total_purchases');

    public function category(){
        return $this->belongsTo('App\Category');
    }

    public function subcategory(){
        return $this->belongsTo('App\Subcategory');
    }

    public function tax(){
        return $this->belongsTo('App\Tax');
    }

    public function purchases() {
        return $this->hasMany('App\Purchase');
    }

    public function getBarCodeAttribute()
    {
        return 'data:image/png;base64,' . DNS1D::getBarcodePNG($this->code, "c128A",1,33,array(1,1,1), true);
    }

    public function order() {
    return $this->hasMany('App\Order');
    }

    public function sells() {
        return $this->hasMany('App\Sell');
    }

    public function challans() {
        return $this->hasMany('App\Representative');
    }


    public function gifts() {
        return $this->hasMany('App\GiftProduct');
    }

    public function damages() {
        return $this->hasMany('App\DamageProduct');
    }



    //Attributes
    public function getTotalQuantityTransactionAttribute()
    {
        $sum1=Product::where('id', $this->id)->sum('quantity');
        return $sum1;
    }
    public function getSumOpeningAttribute()
    {
        $sum1=Product::where('id', $this->id)->sum('opening_stock');
        return $sum1;
    }

    public function getTotalSellsAttribute()
    {
       $sum1=Sell::where('product_id', $this->id)->sum('quantity');
       return $sum1;
    }

    public function getTotalPurchasesAttribute()
    {
        $sum1=Purchase::where('product_id', $this->id)->sum('quantity');
        return $sum1;
    }
    //testing


}
