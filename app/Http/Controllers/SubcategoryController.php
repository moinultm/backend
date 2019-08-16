<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Subcategory;
use App\Traits\Paginator;

class SubcategoryController extends Controller
{
    use Paginator;

    public function index(Request $request): jsonresponse
    {
        $query = Subcategory::query();
        $query->with(['categories']);

        return response()->json(self::paginate($query, $request), 200);
    }

        public function store(Request $request): JsonResponse
        {
            $rules = [
                'subcategory_name' => [
                    'required',
                    'max:255',
                    'unique:subcategories,subcategory_name'
                ]
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
            }
       $subcategory = new Subcategory();
       $subcategory->subcategory_name = ucfirst($request->get('subcategory_name'));
        $subcategory->category_id = $request->get('category_id');
        $subcategory->save();

         return response()->json(Subcategory::where('id', $subcategory->id)->first(), 200);
        }


    public function show($id): JsonResponse
    {
        $query = Subcategory::query();
        $query->where('id', $id);
        return response()->json($query->first(), $query->count() == 0 ? 404 : 200);
    }



}

