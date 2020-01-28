<?php

namespace App\Http\Controllers;

use App\Product;
use App\Representative;
use App\Sell;
use App\Transaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;

use App\Traits\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Traits\Paginator;

class RepresentativeController extends Controller
{
    use Paginator;
    use helpers;



    public function index(Request $request): JsonResponse
    {

        $transactions = Transaction::where('transaction_type', 'transfer')->orderBy('reference_no', 'desc');

        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to = self:: filterTo($to);

        if($request->get('from') || $request->get('to')) {
            if(!is_null($from)){
                $from = Carbon::createFromFormat('Y-m-d',$from);
                $from = self::filterFrom($from);
                $transactions->whereBetween('date',[$from,$to]);
            }else{
                $transactions->where('date','<=',$to);
            }
        }


        return response()->json(self::paginate($transactions, $request), 200);
    }


    public function getChallans(Request $request,$id): JsonResponse
    {

        if ($id==0){
            $transactions = Transaction::where('transaction_type', 'transfer')->orderBy('reference_no', 'desc');
        }
    else{
        $transactions = Transaction::where('transaction_type', 'transfer')
            ->where('user_id','=', $id)
            ->orderBy('date', 'desc');
    }



        return response()->json(self::paginate($transactions, $request), 200);

    }

    public function getUser(): JsonResponse
    {

        $query = User::query()->select('id', 'name', 'address');
        //$query->where('user_type', '2');
        $AssociateArray = array(
            'data' => $query->get()
        );


        return response()->json($AssociateArray, 200);
    }


    public function store(Request $request)
    {

        $customer = $request->get('user_id');

        if (!$customer) {
            throw new ValidationException('user ID is required.');
        }

        $ym = Carbon::now()->format('Y-m');

        $row = Transaction::where('transaction_type', 'transfer')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'transfer')->withTrashed()->get()->count() + 1 : 1;
        $ref_no = 'CH-' . self::ref($row);


        $items = $request->get('items');
        $items = json_decode($items, TRUE);
        //print_r($items);
        $user = 0;

        DB::transaction(function () use ($request, $items, $ref_no) {
            foreach ($items as $sell_item) {
                $stock = new Representative();
                $stock->ref_no = $ref_no;
                $stock->user_id = $request->get('user_id');
                $stock->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
                $stock->product_id = $sell_item['product_id'];
                $stock->quantity = $sell_item['add_quantity'];
                $user = $stock->user_id;
                $stock->save();



                $product = $stock->product;
                $product->general_quantity = $product->general_quantity - intval($sell_item['add_quantity']);
                $product->save();


            }


            $transaction = new Transaction;
            $transaction->reference_no = $ref_no;
            $transaction->client_id = $request->get('user_id');
            $transaction->transaction_type = 'transfer';
            $transaction->discount = 0;
            $transaction->total = 0;
            $transaction->invoice_tax =0;
            $transaction->total_tax = 0;
            $transaction->net_total = 0;
            $transaction->user_id =  $request->get('user_id');
            $transaction->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
            $transaction->paid =0;
            $transaction->save();


        });

        return response()->json(Representative::where('user_id', $user)->first(), 200);

    }


    public function getSells(Request $request, $id): JsonResponse
    {


        $user_id = $request->get('id');

        if ($user_id != '0') {
            $product = Sell::where('sells.user_id', $id)
                ->join('products', 'sells.product_id', '=', 'products.id')
                ->selectRaw('products.name,products.mrp,sum(sells.quantity) as quantity,
                            sells.product_discount_percentage,
                            sum(sells.product_discount_amount)as product_discount_amount,
                            sum(sells.sub_total)as sub_total')
                ->groupBy('products.name', 'products.mrp',
                    'sells.product_discount_percentage'
                );

        } else {
            $product = Sell::query()
                ->join('products', 'sells.product_id', '=', 'products.id')
                ->selectRaw('products.name,products.mrp,sum(sells.quantity) as quantity,
                            sells.product_discount_percentage,
                            sum(sells.product_discount_amount)as product_discount_amount,
                            sum(sells.sub_total)as sub_total')
                ->groupBy('products.name', 'products.mrp',
                    'sells.product_discount_percentage'
                );

        }


        /* $query = Sell::query();
         $query->with(['product']);
         $query->with(['client']);
      */

        return response()->json(self::paginate($product, $request), 200);
    }


    public function getInvoices(Request $request, $id): JsonResponse
    {

//mus assign user id to all

        $user_id = $request->get('id');

        $sells = Transaction:: where('transaction_type', 'sell')->orderBy('date', 'desc');


        /* $query = Sell::query();
         $query->with(['product']);
         $query->with(['client']);

        //for groupping
            ->select('reference_no', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('reference_no');
      */

        return response()->json(self::paginate($sells, $request), 200);
    }

    public function getDetails(Request $request): JsonResponse

    {

        $id=$request->get('ref');

        $query = Transaction::query();
        $query->where('id', $id);
        $query->with(['challans','challans.product']);
        $query->with(['user']);

        $AssociateArray = array('data' =>$query->get());



        $AssociateArray = array('data' => $query->get());

        return response()->json($AssociateArray, 200);

    }

    public function getConformed(Request $request): JsonResponse
    {

//dd($request->get('ref'));

      $user=   Auth::user()->id;

      $transacttion= Transaction::where('reference_no', '=', $request->get('reference_no'))->firstOrFail();


        $receipt = Representative::where('ref_no', '=', $request->get('reference_no'))->firstOrFail();
        //$receipt = Representative::find($request->get('ref'));

            if ( $receipt->user_id <> $user) {
                return response()->json( [ 'error' => 'Receiver ID Not Match'], 403);
            }


        $transacttion->pos= '1';
        $transacttion->save();

        $receipt->receiving= '1';
        $receipt->save();

        return response()->json( [ 'success' => 'Receiving Challan Confirmed'], 200);

     }


    public function deleteChallan(Request $request) {

        $transaction = Transaction::findorFail($request->get('id'));

        foreach ($transaction->challans as $challan) {
            //delete the purchase entry in purchases table

            $product = Product::findorFail($challan->product_id);

            $current_general_stock = $product->general_quantity;
            $product->general_quantity =$current_general_stock + $challan->quantity;
            $product->save();

            $challan->delete();

        }
        //delete the transaction entry for this sale
        $transaction->delete();


        return response()->json( $transaction, 200);

    }

}
