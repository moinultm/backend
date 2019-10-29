<?php

namespace App\Http\Controllers;

use App\Client;
use App\Product;
use App\Sell;
use App\Traits\Paginator;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use Paginator;

    public function index(Request $request): JsonResponse
    {
        $rules = [ 'phone' => 'numeric|min:3'];
        if($request->get('phone')) {
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
            }
        }

        $client = Client::where('client_type', 'customer');

        if($request->get('phone')) {
            $client->where('contact', 'LIKE', '%' . $request->get('phone') . '%');
        }

        return response()->json(self::paginate($client, $request), 200);
    }

    public  function details(Client $client): JsonResponse
    {

          $net_total = $client->transactions->sum('net_total') + $client->returns->sum('return_amount') - $client->provious_due;
          $total_return = $client->payments->where('type', 'return')->sum('amount');
          $total_received = $client->payments->where('type', '!=','return')->sum('amount') - $total_return;
          $total_due = $client->transactions->sum('net_total') - ($total_received);
          $payment_lists = $client->payments()->orderBy('date','desc')->take(10)->get();
         $total_invoice = $client->transactions()->where('transaction_type', '!=','opening')->count();


        $query = compact( 'total_due', 'total_received', 'total_return', 'net_total', 'payment_lists','total_invoice','client');

        $AssociateArray = array(
            'data' =>$query
        );

        //self::paginate()
      return response()->json($AssociateArray  ,200);

    }


    public function store(Request $request){

        $rules = [
            'full_name' => [
                'required',
                'max:255'
            ],
            'contact' => [
                'required',
                'min:9'

            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $client = new Client();
        $client->full_name = ucwords($request->get('full_name'));
        $client->company_name = ucwords($request->get('company_name'));
        $client->email = $request->get('email');
        $client->contact = $request->get('contact');
        $client->address = $request->get('address');
        $client->client_type = 'customer';
        $client->account_no = $request->get('account_no');
              $client->previous_due = $request->get('previous_due');
        $client->client_code = $request->get('client_code');
        $client->save();
        return response()->json( 'success', 200);

    }


    public function update(Request $request,$id){
        $rules = [
            'full_name' => [
                'required',
                'max:255'
            ],
            'contact' => [
                'required',
                'min:9'

            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $client = Client::where('id', $id)->first();;
        $client->full_name = ucwords($request->get('full_name'));
        $client->company_name = ucwords($request->get('company_name'));
        $client->email = $request->get('email');
        $client->contact = $request->get('contact');
        $client->address = $request->get('address');
        $client->client_type = 'customer';
        $client->account_no = $request->get('account_no');
        $client->previous_due = $request->get('previous_due');
        $client->client_code = $request->get('client_code');
        $client->save();
        return response()->json( 'success', 200);

    }


    public function destroy(Request $request, int $id): JsonResponse
    {
        $client = Client::where('id', $id)->first();

        if(($client->sells->count() == 0) && ($client->purchases->count() == 0)){
            $client->delete();

            return response()->json('Deleted', 200);
        }

        return response()->json('Cannot Delete Transaction or Sells found ', 403);
    }


    public function  saleDetails( $id){



        $product= Sell::where('sells.client_id', $id)
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw('products.name,products.mrp,sum(sells.quantity) as quantity,
                            sells.product_discount_percentage,
                            sum(sells.product_discount_amount)as product_discount_amount,
                            sum(sells.sub_total)as sub_total')
            ->groupBy('products.name','products.mrp',
                'sells.product_discount_percentage'
                  );


        return response()->json(self::paginate($product), 200);
    }

}
