<?php

namespace App\Http\Controllers;

use App\Representative;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;

use Illuminate\Validation\ValidationException;

class RepresentativeController extends Controller
{

    public function getUser(): JsonResponse {

        $query = User::query();
        //$query->where('user_type', '2');
        $AssociateArray = array(
            'data' =>  $query->get()
        );


        return response()->json( $AssociateArray, 200);
    }


public function store(Request $request){


    $customer = $request->get('user_id');

    if (!$customer) {
        throw new ValidationException('user ID is required.');
    }



    $items = $request->get('items');
    $items = json_decode($items, TRUE);
  print_r($items);

    DB::transaction(function() use ($request , $items){
        foreach ($items as $sell_item) {
            $stock = new Representative();
            $stock->user_id = $request->get('user_id');
            $stock->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
            $stock->product_id = $sell_item['product_id'];
            $stock->quantity = $sell_item['add_quantity'];

            $stock->save();
        }
    });

    return response()->json( 'data', 200);

}

}
