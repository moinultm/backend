<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{

    public function category(){
        return $this->belongsTo('App\ExpenseCategory');
    }

    public function subcategory(){
        return $this->belongsTo('App\ExpenseSubcategory');
    }

    public function expenses() {
        return $this->hasMany('App\Expenses');
    }
}
