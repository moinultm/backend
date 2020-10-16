<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;


    public function expense_category(){
        return $this->belongsTo('App\ExpenseCategory');
    }
}
