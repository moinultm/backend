<?php

namespace App\Http\Controllers;

use App\ExpenseCategory;
use App\Traits\Helpers;
use App\Traits\Paginator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseCategoryController extends Controller
{
    use Paginator;
    use helpers;

    public function index(Request $request): JsonResponse
    {
        $expenses = ExpenseCategory::orderBy('id', 'desc');
        if($request->get('category_name')) {
            $expenses->where('category_name', 'LIKE', '%' . $request->get('category_name') . '%');
        }

        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to = self::filterTo($to);

        if($request->get('from') || $request->get('to')) {
            if(!is_null($from)){
                $from = Carbon::createFromFormat('Y-m-d',$from);
                $from = self::filterFrom($from);
                $expenses->whereBetween('created_at',[$from,$to]);
            }else{
                $expenses->where('created_at','<=',$to);
            }
        }

        return response()->json(self::paginate($expenses, $request), 200);
    }

    public function show($id): JsonResponse
    {

        return response()->json('', 200);
    }

    public function store(Request $request): JsonResponse
    {

        $rules = [
            'category_name' => [
                'required',
                'max:255',
                'unique:expense_categories,category_name'
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }


        $expense = new ExpenseCategory;
        $expense->category_name = $request->get('category_name');

        //$expense->date =Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
        $expense->save();

        return response()->json('Saved', 200);
    }

    public function update(Request $request,$id): JsonResponse
    {

        $rules = [
            'category_name' => [
                'required',
                'max:255',
                'unique:expense_categories,category_name'
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }


        $expense = ExpenseCategory::find($request->get('id'));
        $expense->category_name = $request->get('category_name');

        $expense->save();

        return response()->json('Success', 200);
    }

    public function destroy(ExpenseCategory   $category): JsonResponse
    {
        if(count($category->subcategories) ==  0 && count($category->product) == 0){
            $category->delete();
            return response()->json(['message' => 'Successfully Deleted'],200 );
        }else{

            return response()->json(['error' => 'cannot  delete subcategory exists'], 403);

        }
    }


}
