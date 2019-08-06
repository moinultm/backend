<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Role;
use Illuminate\Http\JsonResponse;
use App\Traits\Paginator;


class RoleController extends Controller
{
    use Paginator;

    public function index(Request $request)
   {

       $query = Role::query();
       return response()->json(self::paginate($query, $request), 200);
   }

}
