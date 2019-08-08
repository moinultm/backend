<?php

namespace App\Rules;

use App\User;
use Illuminate\Contracts\Validation\Rule;

class UserExists implements Rule
{
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    public function passes($attribute, $value)
    {
        return User::where('id', $this->id)->count() != 0;
    }

    public function message()
    {
        return trans('validation.exists', ['attribute' => 'user']);
    }
}
