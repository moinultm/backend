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

use DB;

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

    public  function details($id): JsonResponse
    {
        $query = Expense::query();
        $query->where('id', $id);

        $query->with(['expense_transactions']);
        $AssociateArray = array('data' =>$query->get());

        return response()->json($AssociateArray  ,200);
    }

    public function store(Request $request): JsonResponse
    {

        $rules = [
            'purpose' => 'required',
            'transaction' => 'required',
            'payment_by' => 'required',
            'user_id' => 'required',



        ];

        $items = $request->get('items');
        $items = json_decode($items, TRUE);

        if (!$items) {
            return  response()->json('Items was not found.');
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $row = Expense::where('transaction', 'expense')->withTrashed()->get()->count() > 0 ? Expense::where('transaction', 'expense')->withTrashed()->get()->count() + 1 : 1;
        $ref_no = 'PV-'.self::ref($row);

        DB::transaction(function() use ($request , $items, $ref_no, &$total ) {

            foreach ($items as $exp_item) {
                if (intval($exp_item['quantity']) === 0) {
                    return response()->json(  'Cannot add Zero value', 403);
                }


                //		amount	transaction	payment_details	payment_by	date	user_id	category
                $expenseTran = new ExpenseTransaction;

                $expenseTran->reference_no = $ref_no;
                $expenseTran->transaction_no = $ref_no;
                // 	 	 	user_id	transaction	total	tran_by 	date	created_at	updated_at

                $total = $total + $exp_item['quantity'];

                $expenseTran->ledger_name = $exp_item['expense_id'];
                $expenseTran->transaction =   $request->get('transaction');
                $expenseTran->tran_by =  $request->get('payment_by');
                $expenseTran->total = $exp_item['quantity'];
                $expenseTran->details = $exp_item['details']  ;
                $expenseTran->user_id = $request->get('user_id'); ;
                $expenseTran->save();
            }

            $expense = new Expense;
            $expense->reference_no = $ref_no;

            $expense->expense_item_id = 0;
            $expense->ledger_name = '';
            $expense->category = 0;

            $expense->transaction = $request->get('transaction');
            $expense->date =Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
            $expense->payment_by = $request->get('payment_by');
            $expense->payment_details = $request->get('payment_details');
            $expense->purpose = $request->get('purpose');
            $expense->amount =$total;
            $expense->user_id = $request->get('user_id');

            $expense->save();


        });


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
