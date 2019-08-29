<?php

namespace App\Http\Controllers;

use App\Product;
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

    public function parentReq(Request $request): JsonResponse
    {
        $category_id = $request->get('categoryId');
        $query = Subcategory::where('category_id', $category_id);
        return response()->json(self::paginate($query, $request), 200);

    }



    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'subcategory_name' => [
                'required',
                'max:255',
                'unique:subcategories,subcategory_name,' . $id
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $subcategory = Subcategory::where('id', $id)->first();
        $subcategory->subcategory_name = $request->get('subcategory_name');

        $subcategory->save();
        return response()->json(Subcategory::where('id', $subcategory->id)->first(), 200);
    }
    public function destroy(Subcategory $subcategory): JsonResponse
    {


        if(count($subcategory->products) ===  0){
            $subcategory->delete();
            return response()->json(['message' => 'Successfully Deleted'],200 );
        }else{

            return response()->json(['error' => 'cannot  delete Product exists'], 403);

        }

    }


    public function productList(Request $request,$id){

        $query=Product::query()->where('subcategory_id', $id);
        return response()->json(self::paginate($query), $query->count() == 0 ? 403 : 200);

    }

}

