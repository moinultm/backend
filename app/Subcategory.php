<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subcategory extends Model
{
    public $fillable = [
        'subcategory_name',
        'category_id'
    ];
    public function __construct(array $attributes = [])
{
    parent::__construct($attributes);
    $this->table = 'subcategories';
}
    public function categories(): BelongsTo
    {
        return $this->belongsTo(Category::class,'category_id');
    }

    public function products(){
        return $this->hasMany('App\Product');
    }

    public function sells () {
        return $this->hasManyThrough('App\Sell', 'App\Product');
    }

}
