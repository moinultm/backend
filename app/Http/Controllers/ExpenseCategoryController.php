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
        if($request->get('name')) {
            $expenses->where('name', 'LIKE', '%' . $request->get('name') . '%');
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
            'name' => [
                'required',
                'max:255',
                'unique:expense_categories,name'
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }


        $expense = new ExpenseCategory;
        $expense->name = $request->get('name');

        //$expense->date =Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
        $expense->save();

        return response()->json('Saved', 200);
    }

    public function update(Request $request,$id): JsonResponse
    {

        $rules = [
            'name' => [
                'required',
                'max:255',
                'unique:expense_categories,name'
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }


        $expense = ExpenseCategory::find($request->get('id'));
        $expense->title = $request->get('name');

        $expense->save();




        return response()->json('Success', 200);
    }

    public function destroy(Request $request,int $id): JsonResponse
    {
        $expense = ExpenseCategory::where('id', $id)->first();
        $expense->delete();

        return response()->json('Success', 200);
    }

}
