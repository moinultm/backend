<?php

namespace App\Http\Controllers;

use App\Traits\Paginator;

use App\User;
use App\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    use Paginator;


    public function index(Request $request): JsonResponse
    {
        $warehouses = Warehouse::orderBy('name', 'asc');
        return response()->json(self::paginate($warehouses, $request), 200);
    }

    public function show($id): JsonResponse
    {
        return response()->json( 'no-data'  , 200);
    }

    public function store(Request $request): JsonResponse
    {


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

        $warehouse = new Warehouse;
        $warehouse->name = $request->get('name');
        $warehouse->address = $request->get('address');
        $warehouse->phone = $request->get('phone');
        $warehouse->in_charge_name = $request->get('in_charge_name');
        $warehouse->save();

        $message ='saved';
        return response()->json($message, 200);
    }

    public function update(Request $request,$id): JsonResponse
    {

        $rules = [
            'name' => [
                'required',
                'max:255'
            ]
        ];

        $warehouse = Warehouse::where('id', $id)->first();

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }
         $warehouse->name = $request->get('name');
        $warehouse->address = $request->get('address');
        $warehouse->phone = $request->get('phone');
        $warehouse->in_charge_name = $request->get('in_charge_name');
        $warehouse->save();

        $message ='saved';
        return response()->json($message, 200);

    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {

        $exists = User::where('warehouse_id', $warehouse->id)->count();
        if($exists > 0){
            $message = "You can't delete this branch. User Exist";
            return response()->json($message, 403);

        }else{
            if(count($warehouse->transactions) ==  0){
                $warehouse->delete();
                $message = 'Deleted';
                return response()->json($message, 200);    }else{
                $message = 'Cannot Deleted. Transaction Exist';
                return response()->json($message, 403);
            }
        }

    }


}
