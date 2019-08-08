<?php

namespace App\Rules;

use App\Role;
use Illuminate\Contracts\Validation\Rule;

class RoleExists implements Rule
{
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    public function passes($attribute, $value)
    {
        return Role::where('id', $this->id)->count() != 0;
    }

    public function message()
    {
        return trans('validation.exists', ['attribute' => 'role']);
    }
}
