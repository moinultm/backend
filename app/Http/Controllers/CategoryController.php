<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\category;
use App\Traits\Paginator;
use Illuminate\Http\JsonResponse;


class CategoryController extends Controller
{
    use paginator;

    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        return response()->json(self::paginate($query, $request), 200);

    }
    public function store(Request $request): JsonResponse
    {
        return response()->json(self::paginate('', $request), 200);
    }

    public function show($id): JsonResponse
    {
        return response();

    }


    public function update(Request $request, $id): JsonResponse
    {
        return response()->json(self::paginate('', $request), 200);

    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return response();
    }



    }
