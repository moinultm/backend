<?php

namespace App\Http\Controllers;

use App\ExpenseItem;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Traits\Paginator;

class ExpenseItemController extends Controller
{
    use Paginator;

    public function index(Request $request): jsonresponse
    {
        $query = ExpenseItem::query();
       // $query->with(['expensescategories']);

        return response()->json(self::paginate($query, $request), 200);
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'ledger_name' => [
                'required',
                'max:255',
                'unique:expense_items,ledger_name'
            ],
            'expense_category_id' => [
                'required'
            ],
            'expense_subcategory_id' => [
          'required'
            ]

        ];


        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }
        $subcategory = new ExpenseItem();
        $subcategory->ledger_name =  $request->get('ledger_name') ;
        $subcategory->item_name =  $request->get('ledger_name') ;
        $subcategory->expense_category_id = $request->get('expense_category_id');
        $subcategory->expense_subcategory_id = $request->get('expense_subcategory_id');

        $subcategory->opening_Balance = 0;
        $subcategory->closing_Balance = 0;

        $subcategory->save();

        return response()->json(ExpenseItem::where('id', $subcategory->id)->first(), 200);
    }


    public function show($id): JsonResponse
    {
        $query = ExpenseItem::query();
        $query->where('id', $id);
        return response()->json($query->first(), $query->count() == 0 ? 404 : 200);
    }

    public function parentReq(Request $request): JsonResponse
    {
        $category_id = $request->get('categoryId');
        $query = ExpenseItem::where('category_id', $category_id);
        return response()->json(self::paginate($query, $request), 200);

    }


    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'ledger_name' => [
                'required',
                'max:255',
                'unique:expense_items,ledger_name'
            ],
            'expense_category_id' => [
                'required'
            ],
            'expense_subcategory_id' => [
                'required'
            ]

        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $subcategory = ExpenseItem::where('id', $id)->first();
        $subcategory->ledger_name = $request->get('ledger_name');
        $subcategory->expense_category_id = $request->get('expense_category_id');
        $subcategory->expense_subcategory_id = $request->get('expense_subcategory_id');

        $subcategory->save();
        return response()->json(ExpenseItem::where('id', $subcategory->id)->first(), 200);
    }

    public function destroy(ExpenseItem $subcategory): JsonResponse
    {

        if(count($subcategory->expenses) ===  0){
            $subcategory->delete();
            return response()->json(['message' => 'Successfully Deleted'],200 );
        }else{

            return response()->json(['error' => 'cannot  delete Expense exists'], 403);

        }

    }

}
