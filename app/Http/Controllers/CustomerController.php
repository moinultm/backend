<?php

namespace App\Http\Controllers;

use App\Client;
use App\Traits\Paginator;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use Paginator;

    public function index(Request $request): JsonResponse
    {
        $query = Client::query();
        return response()->json(self::paginate($query, $request), 200);
    }
}
