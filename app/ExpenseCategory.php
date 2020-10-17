<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{

    public $fillable = [
        'category_name'
    ];


    public function expense () {
        return $this->hasMany('App\ExpenseItem');
    }

    public function subcategories(){
        return $this->hasMany('App\ExpenseSubcategory');
    }

    public function expenses () {
        return $this->hasManyThrough('App\ExpenseItem', 'App\ExpenseSubcategory');
    }

}
