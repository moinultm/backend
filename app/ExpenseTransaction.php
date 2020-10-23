<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ExpenseTransaction extends Model
{
    use SoftDeletes;


    public function expenseitem(){
        return $this->belongsTo('App\ExpenseItem');
    }



    public function user()
    {
        return $this->belongsTo('App\User');
    }

}
