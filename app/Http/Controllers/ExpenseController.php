<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Expense;
use App\ExpenseTransaction;
use App\Traits\Paginator;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Traits\Helpers;
use Illuminate\Support\Facades\Validator;


class ExpenseController extends Controller
{
    use Paginator;
    use helpers;

    public function index(Request $request): JsonResponse
    {
        $expenses = Expense::orderBy('id', 'desc');
        if($request->get('purpose')) {
            $expenses->where('purpose', 'LIKE', '%' . $request->get('purpose') . '%');
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
            'purpose' => 'required',
            'amount' => 'required|numeric',

        ];

        $items = $request->get('items');
        $items = json_decode($items, TRUE);

        if (!$items) {
            return  response()->json('Itemsiiiiiii8 was not found.');
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $row = Expense::where('transaction', 'expense')->withTrashed()->get()->count() > 0 ? Expense::where('transaction', 'expense')->withTrashed()->get()->count() + 1 : 1;
        $ref_no = 'PV-'.self::ref($row);

        foreach ($items as $sell_item) {
            if (intval($sell_item['amount']) === 0) {
                throw new ValidationException('Cannot add Zero value');
            }

            $expenseTran = new ExpenseTransaction;

            $expenseTran->reference_no = $ref_no;
            $expenseTran->transaction_no = $ref_no;

            $expenseTran->ledger_name = $request->get('ledger_name');
            $expenseTran->user_id = $request->get('user_id');

        }

        $expense = new Expense;
        $expense->reference_no = $ref_no;

        $expense->purpose = $request->get('purpose');
        $expense->amount = $request->get('amount');
        $expense->user_id = $request->get('user_id');
        $expense->transaction = $request->get('transaction');

        $expense->date =Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
        $expense->save();

        return response()->json('Saved', 200);
    }

    public function update(Request $request,$id): JsonResponse
    {

        $rules = [
            'purpose' => 'required',
            'amount' => 'required|numeric',
            'user_id' => 'required|numeric',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }


        $expense = Expense::find($request->get('id'));
            $expense->purpose = $request->get('purpose');
            $expense->amount = $request->get('amount');
        $expense->user_id = $request->get('user_id');
        $expense->save();




        return response()->json('Success', 200);
    }

    public function destroy(Request $request,int $id): JsonResponse
    {
        $expense = Expense::where('id', $id)->first();
        $expense->delete();

        return response()->json('Success', 200);
    }

}
