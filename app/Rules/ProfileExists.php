<?php

namespace App\Rules;

use App\Profile;
use Illuminate\Contracts\Validation\Rule;

class ProfileExists implements Rule
{
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function passes($attribute, $value)
    {
        return Profile::where('id', $this->id)->count() != 0;
    }


    public function message()
    {
        return trans('validation.exists', ['attribute' => 'profile']);
    }
}
