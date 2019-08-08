<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Http\JsonResponse;
use App\Traits\Paginator;

class UsersController extends Controller
{
    use Paginator;

    public function index(Request $request): JsonResponse
    {
        $query = User::query();
        $query->with(['profiles']);
        return response()->json(self::paginate($query, $request), 200);
    }

}
