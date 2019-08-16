<?php

namespace App\Rules;

use App\Category;
use Illuminate\Contracts\Validation\Rule;

class CategoryExists implements Rule
{
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    public function passes($attribute, $value)
    {
        return Category::where('id', $this->id)->count() != 0;
    }

    public function message()
    {
        return trans('validation.exists', ['attribute' => 'categoty_name']);
    }
}
