<?php

namespace App\Http\Controllers;

use App\Rules\CategoryExists;
use App\Subcategory;
use Illuminate\Http\Request;
use App\Category;
use App\Traits\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    use paginator;

    public function index(Request $request): JsonResponse
    {
        $query = Category::query();
        if($request->get('name')) {
            $query->where(function($q) use($request) {
                $q->where('name', 'LIKE', '%' . $request->get('name') . '%');
            });
        }
        return response()->json(self::paginate($query, $request), 200);

    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
        'category_name' => [
            'required',
            'max:255',
            'unique:categories,category_name'
        ]
    ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }
        $category = new Category();
        $category->category_name = $request->get('category_name');

        $category->save();
        return response()->json(Category::where('id', $category->id)->first(), 200);
    }

    public function show($id): JsonResponse
    {
        $query = Category::query();
        $query->where('id', $id);
        return response()->json($query->first(), $query->count() == 0 ? 404 : 200);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'category_name' => [
                'required',
                'max:255',
                 new CategoryExists($id),
                'unique:categories,category_name,' . $id
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $category = Category::where('id', $id)->first();
        $category->category_name = $request->get('category_name');

        $category->save();
        return response()->json(Category::where('id', $category->id)->first(), 200);
    }

    public function destroy(Category $category): JsonResponse
    {


        if(count($category->subcategories) ==  0 && count($category->product) == 0){
            $category->delete();
                        return response()->json(['message' => 'Successfully Deleted'],200 );
        }else{

            return response()->json(['error' => 'cannot  delete subcategory exists'], 403);

        }

    }


    }
