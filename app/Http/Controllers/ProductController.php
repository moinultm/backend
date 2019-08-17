<?php

namespace App\Http\Controllers;

use App\Product;
use App\Traits\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use Paginator;

    public function index(Request $request): JsonResponse
    {
        $query = Product::query();
        return response()->json(self::paginate($query, $request), 200);
    }
}
