<?php

namespace App\Http\Controllers;

use App\Expense;
use App\Traits\Paginator;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Traits\Helpers;


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

    public function store($id): JsonResponse
    {

        return response()->json('', 200);
    }

    public function update(Request $request,$id): JsonResponse
    {

        return response()->json('', 200);
    }

    public function destroy(Expense $warehouse): JsonResponse
    {

        return response()->json('', 200);
    }

}
