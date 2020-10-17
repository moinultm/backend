<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ExpenseSubcategory extends Model
{


    protected $table = 'expense_subcategories';

    public $fillable = [
        'subcategory_name',
        'category_id'
    ];
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = 'expense_subcategories';
    }

    public function expensescategories(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class,'category_id');
    }



    public function expense(){
        return $this->hasMany('App\ExpenseItem');
    }



}
