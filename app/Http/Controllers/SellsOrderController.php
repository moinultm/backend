<?php

namespace App\Http\Controllers;

use App\Client;
use App\Order;
use App\Payment;
use App\Product;
use App\Representative;
use DB;
use App\Sell;
use App\Traits\Helpers;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exceptions\ValidationException;
use App\Traits\Paginator;
use Illuminate\Http\JsonResponse;


class SellsOrderController extends Controller
{

    use helpers;
    use paginator;

    public function index(Request $request)
    {

        $transactions = Transaction::where('transaction_type', 'ORDER')->orderBy('date', 'desc');

        $from = $request->get('from');
        $to=$request->get('to');

        if( $request->get('from') !='null' &&  $request->get('to')!='null' ) {
            $from = $request->get('from');
            $to = $request->get('to')?:date('Y-m-d');
            $to = Carbon::createFromFormat('Y-m-d',$to);
            $to = self::filterTo($to);
        }


        if( $request->get('from') !='null' &&   $request->get('to')!='null' ) {

            if($request->get('from') || $request->get('to')) {
                if(!is_null($from)){
                    $from = Carbon::createFromFormat('Y-m-d',$from);
                    $from = self::filterFrom($from);
                    $transactions->whereBetween('date',[$from,$to]);
                }else{
                    $transactions->where('date','<=',$to);
                }
            }

        }


        $transactions->with(['user']);
        $transactions->with(['client']);

        return response()->json(self::paginate($transactions, $request), 200);
    }

    public function show(Request $request)
    {

    }


    public function store(Request $request)
    {
        $customer = $request->get('customer');
        $enableProductTax = 0;

        if (!$customer) {
            throw new ValidationException('Customer ID is required.');
        }

        $ym = Carbon::now()->format('Y/m');


        $row = Transaction::where('transaction_type', 'ORDER')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'ORDER')->withTrashed()->get()->count() + 1 : 1;
        $ref_no_t = $ym.'/SO-'.self::ref($row);


        $row = Representative::where('quantity' , '<','0')->withTrashed()->get()->count() > 0 ? Representative::where('quantity' , '>','0')->withTrashed()->get()->count() + 1 : 1;
        $ref_no = $ym.'/SO-'.self::ref($row);

        $totalProductTax = 0;
        $productTax = 0;

        $orders = $request->get('orders');
        $orders = json_decode($orders, TRUE);

        DB::transaction(function() use ($request , $orders, $ref_no, &$total, &$total_cost_price, &$totalProductTax, $customer,$productTax,$ref_no_t  ) {
            foreach ($orders as $order_item) {

                if (intval($order_item['quantity']) === 0) {
                    throw new ValidationException('Product quantity is required');
                }
                if (!$order_item['product_id'] || $order_item['product_id'] === '') {
                    throw new ValidationException('Product ID is required');
                }

                $total = $total + $order_item['item_total'];
                $total_cost_price = $total_cost_price + ($order_item['cost_price'] * $order_item['quantity']);

                $order = new Order();
                $order->reference_no = $ref_no_t;
                $order->product_id = $order_item['product_id'];
                $order->quantity = $order_item['quantity'];
                $order->product_discount_percentage = $order_item['product_discount_percentage'];
                $order->product_discount_amount = $order_item['product_discount_amount'];
                $order->unit_cost_price = $order_item['cost_price'];
                $order->sub_total = $order_item['subtotal']- $productTax;
                $order->client_id = $customer;
                $order->date = Carbon::parse($request->get('date'))->format('Y-m-d');
                $order->user_id = $request->get('user_id');

                $order->save();

            }


            //discount
            $discount = $request->get('discount');
            $discountType = $request->get('discountType');
            $discountAmount = $discount;
            if($discountType == 'percentage'){
                $discountAmount = $total * (1 * $discount / 100);
            }

            $total_payable = $total - $discountAmount;

//This is nesseray as mother table
            $invoice_tax = 0;

            $transaction = new Transaction;
            $transaction->reference_no = $ref_no_t;
            $transaction->client_id = $customer;
            $transaction->transaction_type = 'ORDER';
            $transaction->total_cost_price = $total_cost_price;
            $transaction->discount =  $discountAmount;
            //saving total without product tax and shipping cost
            $transaction->total = $total_payable - $totalProductTax;
            $transaction->invoice_tax = round($invoice_tax, 2);
            $transaction->total_tax = round(($totalProductTax + $invoice_tax), 2);
            $transaction->labor_cost = $request->get('shipping_cost');
            $transaction->net_total = round(($total_payable + $request->get('shipping_cost') + $invoice_tax), 2);
            $transaction->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
            $transaction->paid = 0;
            $transaction->user_id = $request->get('user_id');
            $transaction->save();


        });

        return response()->json( Order::where('reference_no', $ref_no), 200);

    }


    public function edit(Request $request)
    {

    }


    public function destroy(Request $request)
    {

    }


    public  function details($id): JsonResponse
    {
        $query = Transaction::query();
        $query->where('transaction_type', 'ORDER');
        $query->where('id', $id);
        $query->with(['order','order.product']);
         $query->with(['client']);

        $AssociateArray = array('data' =>$query->get());

        return response()->json($AssociateArray  ,200);
    }


    public  function getAlter($id): JsonResponse
    {
        $query = Transaction::query();
        $query->where('transaction_type', 'ORDER');
        $query->where('id', $id);
        $query->with(['order','order.product']);
        $query->with(['client']);


        return response()->json($query->first()  ,200);
    }

}
