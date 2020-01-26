<?php

namespace App\Http\Controllers;

use App\Category;
use App\Product;
use App\Subcategory;
use App\Tax;
use App\Traits\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use Paginator;

    public function index(Request $request): JsonResponse
    {
        $query = Product::query();
        return response()->json(self::paginate($query, $request), 200);
    }

    public function show($id): JsonResponse
    {
        $query = Product::query();
        $query->where('id', $id);
        return response()->json($query->first(), $query->count() == 0 ? 404 : 200);
    }


    public function productDetails($id) : JsonResponse
    {

        $query = Product::query();
        $query->where('id', $id);

        $query->with(['sells','sells.client']);
        $query->with(['purchases','purchases.client']);

       return response()->json(self::paginate($query), $query->count() == 0 ? 404 : 200);
    }



        //Saving Function
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'name' => [
                'required',
                'max:255'
            ],
            'code' => [
                'required',
                'max:255',
                'unique:products,code'
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $product = new Product();

        $checkProductByCode = Product::where('code', $request->get('code'))->get();
        $checkProductByName = Product::where('name', $request->get('name'))->get();

        if ($product->id == null) {
            if ($checkProductByName->count() != 0) {
                $errors = $request->get('name') . " Already Exist!";
                //return redirect()->back()->withInput($request->input())->withErrors($errors);
                return response()->json($errors, 403);
            }

            if ($checkProductByCode->count() != 0) {
                $errors = "Duplicate Product Code (" . $request->get('code') . ") !";
                return response()->json($errors, 403);
            }
        }


        $product->category_id = $request->get('category_id');
        $product->subcategory_id = $request->get('subcategory_id');
        $product->name = $request->get('name');
        $product->code = $request->get('code');

        $product->cost_price = $request->get('cost_price');
        $product->mrp = $request->get('mrp');
        $product->minimum_retail_price = $request->get('minimum_retail_price');
        $product->tax_id = $request->get('tax_id');
        $product->unit = $request->get('unit');
        $product->details = $request->get('details');
        $product->status = $request->get('status') ? $request->get('status') : 0;

        //opening stocks
        $current_stock = ($product->id) ? $product->quantity : 0;
        $product->quantity = $current_stock + $request->get('opening_stock');
        $product->general_quantity = $current_stock + $request->get('opening_stock');
        $product->opening_stock = $request->get('opening_stock');

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $file_extension = $file->getClientOriginalExtension();
            $random_name = str_random(12);
            $destination_path = public_path() . '/uploads/products/';
            $filename = $random_name . '.' . $file_extension;
            $request->file('image')->move($destination_path, $filename);

            $product->image = $filename;
        }

        $message = trans('core.changes_saved');
        $product->save();

        return response()->json($message, 200);

    }

    //Update Product
    public function update(Request $request, $id): JsonResponse
    {
       /** $current_locale = app()->getLocale();
       \App::setLocale('ar');
       $secondary_lang = \Lang::get('core');
       \App::setLocale($current_locale); **/


        $rules = [
                'name' => [
                'required',
                'max:255'
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $product = Product::where('id', $id)->first();


        $product->category_id = $request->get('category_id');
        $product->subcategory_id = $request->get('subcategory_id');
        $product->name = $request->get('name');
        $product->code = $request->get('code');

        $product->cost_price = $request->get('cost_price');
        $product->mrp = $request->get('mrp');
        $product->minimum_retail_price = $request->get('minimum_retail_price');
        $product->tax_id = $request->get('tax_id');
        $product->unit = $request->get('unit');
        $product->details = $request->get('details');
        $product->status = $request->get('status') ? $request->get('status') : 0;

        //opening stocks
        $current_stock = ($product->id) ? $product->quantity : 0;
        $product->quantity = $current_stock + $request->get('opening_stock');
       // $product->general_quantity = $current_stock + $request->get('opening_stock');
        $product->opening_stock = $request->get('opening_stock');

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $file_extension = $file->getClientOriginalExtension();
            $random_name = str_random(12);
            $destination_path = public_path() . '/uploads/products/';
            $filename = $random_name . '.' . $file_extension;
            $request->file('image')->move($destination_path, $filename);

            $product->image = $filename;
        }

        $product->save();

        return response()->json('data', 200);

        //return view('products.form')->withProduct($product)->withSubcategories($subcategories)->withCategories($categories)->withTaxes($taxes)->with('secondary_lang', $secondary_lang);

    }




    public function updatePrice(Request $request){
        $id = $request->get('id');
        $product = Product::find($id);
        $product->cost_price = $request->get('cost_price');
        $product->mrp = $request->get('mrp');
        $product->minimum_retail_price = $request->get('minimum_retail_price');
       $product->save();

         return response()->json( [ 'error' => 'updated'], 200);
    }


}
