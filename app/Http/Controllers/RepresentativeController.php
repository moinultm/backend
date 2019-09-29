<?php

namespace App\Http\Controllers;

use App\Representative;
use App\Sell;
use App\Transaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;

use App\Traits\Helpers;

use Illuminate\Validation\ValidationException;
use App\Traits\Paginator;

class RepresentativeController extends Controller
{
    use Paginator;
    use helpers;

    public function index(Request $request): JsonResponse {


        $query = Representative::where('quantity' , '>=','0')
             ->selectRaw('id,ref_no, sum(quantity)as total_quantity ,date')
                   ->groupBy('id','ref_no','date')
            ->orderBy('ref_no', 'DESC');


        return response()->json(self::paginate($query, $request), 200);
    }



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

    $ym = Carbon::now()->format('Y/m');

    $row = Representative::where('quantity' , '>','0')->withTrashed()->get()->count() > 0 ? Representative::where('quantity' , '>','0')->withTrashed()->get()->count() + 1 : 1;
    $ref_no = $ym.'/RI-'.self::ref($row);


    $items = $request->get('items');
    $items = json_decode($items, TRUE);
  //print_r($items);
    $user =0;

    DB::transaction(function() use ($request,$items,$ref_no){
        foreach ($items as $sell_item) {
            $stock = new Representative();
            $stock->ref_no =$ref_no;
            $stock->user_id = $request->get('user_id');
            $stock->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
            $stock->product_id = $sell_item['product_id'];
            $stock->quantity = $sell_item['add_quantity'];
            $user=  $stock->user_id;
            $stock->save();
        }
    });

    return response()->json( Representative::where('user_id', $user)->first(), 200);

}


    public function getSells(Request $request,$id): JsonResponse {


        $user_id = $request->get('id');

        if($user_id != '0'){
            $product= Sell::where('sells.user_id', $id)
                ->join('products', 'sells.product_id', '=', 'products.id')
                ->selectRaw('products.name,products.mrp,sum(sells.quantity) as quantity,
                            sells.product_discount_percentage,
                            sum(sells.product_discount_amount)as product_discount_amount,
                            sum(sells.sub_total)as sub_total')
                ->groupBy('products.name','products.mrp',
                    'sells.product_discount_percentage'
                );

        }else{
            $product= Sell::query()
                ->join('products', 'sells.product_id', '=', 'products.id')
                ->selectRaw('products.name,products.mrp,sum(sells.quantity) as quantity,
                            sells.product_discount_percentage,
                            sum(sells.product_discount_amount)as product_discount_amount,
                            sum(sells.sub_total)as sub_total')
                ->groupBy('products.name','products.mrp',
                    'sells.product_discount_percentage'
                );

        }



       /* $query = Sell::query();
        $query->with(['product']);
        $query->with(['client']);
     */

        return response()->json( self::paginate($product, $request), 200);
    }



    public function getInvoices(Request $request,$id): JsonResponse {

//mus assign user id to all

         $user_id = $request->get('id');

        $sells= Transaction:: where('transaction_type', 'sell')->orderBy('date', 'desc');



        /* $query = Sell::query();
         $query->with(['product']);
         $query->with(['client']);

        //for groupping
            ->select('reference_no', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('reference_no');
      */

        return response()->json( self::paginate($sells, $request), 200);
    }

    public function getDetails(Request $request,$id): JsonResponse

    {


        $query = Representative::query();
        $query->where('id', $id);
        $query->with(['product']);
        $query->with(['user']);

        $AssociateArray = array('data' =>$query->get());

            return response()->json( $AssociateArray, 200);

    }

}