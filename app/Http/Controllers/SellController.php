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




class SellController extends Controller
{
    use helpers;
use paginator;




    public function index(Request $request)
    {
        $transactions = Transaction::where('transaction_type', 'sell')->orderBy('date', 'desc') ;
        if($request->get('invoice')) {
            $transactions->where('reference_no', 'LIKE', '%' . $request->get('invoice') . '%');
        }

        if($request->get('customer')) {
            $transactions->whereClientId($request->get('customer'));
        }

        if($request->get('type') == 'pos') {
            $transactions->wherePos(1);
        }

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


        $cloneTransactionForNetTotal = clone $transactions;
        $cloneTransactionForTotalTax = clone $transactions;
        $cloneTransactionForInvoiceTax = clone $transactions;
        $cloneTransactionForTotalCostPrice = clone $transactions;

        $invoice_tax = $cloneTransactionForInvoiceTax->sum('invoice_tax');
        $total_tax = $cloneTransactionForTotalTax->sum('total_tax');
        $product_tax = $total_tax - $invoice_tax;

        $net_total = $cloneTransactionForNetTotal->sum('net_total');
        $total = $net_total - $total_tax;

        $total_cost_price = $cloneTransactionForTotalCostPrice->sum('total_cost_price');

        $profit = $total - $total_cost_price;


     // $query = compact( 'transactions');
        //'transactions' =>$transactions,
         //   'customers'=>$customers,
    /*
     * $AssociateArray = array(
                'data'=>$transactions,
                'net_total'=>$net_total,
                'invoice_tax'=>$invoice_tax,
                'product_tax'=>$product_tax,
                'total'=>$total,
                'total_cost_price'=>$total_cost_price,
                'profit'=>$profit,
                'count'=>'5',
                'size'=>'1',
                'page'=>'1',
            );
    */

      //return response()->json( $AssociateArray , 200);
 return response()->json(self::paginate($transactions, $request), 200);
    }


    public function getLists(): JsonResponse

    {
        Transaction::$preventAttrSet=true;

        $transactions = Transaction::select('id','reference_no')->where('transaction_type', 'sell')->orderBy('date', 'desc')->get();
        $AssociateArray = array(
            'data' =>  $transactions
        );
        return response()->json($AssociateArray ,200);
    }


    public function store(Request $request)
    {

//11-10-2019 update then sells order table after insertion

        //we have disabled the taxes and settings checkup
        $customer = $request->get('customer');
        $order_no = $request->get('order_no');

        $stock=$request->get('order_no');

        $enableProductTax = 0;

        if (!$customer) {
            throw new ValidationException('Customer ID is required.');
        }

        $ym = Carbon::now()->format('Y/m');

        $row = Transaction::where('transaction_type', 'sell')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'sell')->withTrashed()->get()->count() + 1 : 1;
        $ref_no = 'SI-'.self::ref($row);

        $total = 0;
        $totalProductTax = 0;
        $productTax = 0;
        $total_cost_price = 0;

        $row = Representative::where('quantity' , '<','0')->withTrashed()->get()->count() > 0 ? Representative::where('quantity' , '>','0')->withTrashed()->get()->count() + 1 : 1;
        $ref_no_rep_sell = 'RP-'.self::ref($row);

            if ($order_no=="0") {
                $row = Transaction::where('transaction_type', 'ORDER')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'ORDER')->withTrashed()->get()->count() + 1 : 1;
                $order_no_new = 'SO-'.self::ref($row);
            }
            else {
                $order_no_new=$order_no;
            }

       // dd($order_no_new);


        $paid = floatval($request->get('paid')) ?: 0;

        $sells = $request->get('sells');
        $sells = json_decode($sells, TRUE);
       // print_r($sells);

        DB::transaction(function() use ($request , $sells, $ref_no, &$total, &$total_cost_price, &$totalProductTax, $customer, $paid, $enableProductTax, $productTax,$ref_no_rep_sell,$order_no,$order_no_new){
            foreach ($sells as $sell_item) {

                if (intval($sell_item['quantity']) === 0) {
                    throw new ValidationException('Product quantity is required');
                }

                if (!$sell_item['product_id'] || $sell_item['product_id'] === '') {
                    throw new ValidationException('Product ID is required');
                }

                $product_row = Product::findorFail($sell_item['product_id']);
                $cost_price=$product_row->cost_price;
                $product_stock=$product_row->quantity ;

                if ($product_stock <  0) {
                    throw new ValidationException('Null Stock Item');
                }


                $total = $total + $sell_item['sub_total'];
                $total_cost_price = $total_cost_price + ($cost_price * $sell_item['quantity']);

                $sell = new Sell;
                $sell->reference_no = $ref_no;
                $sell->order_no = $order_no_new;
                $sell->product_id = $sell_item['product_id'];
                $sell->quantity = $sell_item['quantity'];
                $sell->product_discount_percentage = $sell_item['product_discount_percentage'];
                $sell->product_discount_amount = $sell_item['product_discount_amount'];

                if($enableProductTax == 1){
                    //product tax calculation
                    $product_row = Product::findorFail($sell_item['product_id']);
                    $taxRate = $product_row->tax->rate;
                    $taxType = $product_row->tax->type;

                    $productTax = ($taxType == 1) ? (($sell_item['quantity'] * $taxRate * $sell_item['price']) / 100) : ($sell_item['quantity'] * $taxRate);

                    $sell->product_tax = $productTax;
                    //ends
                    $totalProductTax = $totalProductTax + $productTax;
                }


                $sell->unit_cost_price = $cost_price;
                $sell->sub_total = $sell_item['sub_total']- $productTax;
                $sell->client_id = $customer;
                $sell->date = Carbon::parse($request->get('date'))->format('Y-m-d');
                $sell->user_id = $request->get('user_id');
                $sell->direct = $request->get('direct');
                $sell->save();


                if ($order_no=="0"){
                    $order = new Order();
                    $order->reference_no = $order_no_new;
                    $order->product_id = $sell_item['product_id'];
                    $order->quantity = $sell_item['quantity'];
                    $order->invoiced_qty = $sell_item['quantity'];
                    $order->product_discount_percentage = $sell_item['product_discount_percentage'];
                    $order->product_discount_amount = $sell_item['product_discount_amount'];
                    $order->unit_cost_price = $cost_price;
                    $order->sub_total = $sell_item['sub_total']- $productTax;

                    $order->sub_total = $sell_item['batch_no'];
                    $order->sub_total = $sell_item['lot_no'];
                    $order->sub_total = $sell_item['pack_size'];
                    $order->sub_total = $sell_item['mfg_date'];
                    $order->sub_total = $sell_item['exp_date'];

                    $order->client_id = $customer;
                    $order->date = Carbon::parse($request->get('date'))->format('Y-m-d');
                    $order->user_id = $request->get('user_id');
                    //$order->direct = $request->get('direct');
                    $order->save();

                }
                else{

                    //$order=Order::findorFail($order_no);
                    $order=Order::where('reference_no', $order_no)->firstOrFail();
                    $order->invoiced_qty = $order->invoiced_qty + intval($sell_item['quantity']);
                    $order->save();
                }

            if ($request->get('direct')==0) {
                //Representative Decrements
                $stock = new Representative();
                $stock->user_id = $request->get('user_id');
                $stock->date = Carbon::parse($request->get('date'))->format('Y-m-d');
                $stock->product_id = $sell_item['product_id'];
                $stock->quantity =  $sell_item['quantity']*-1;
                $stock->ref_no= $ref_no_rep_sell;
                $stock->save();
            }

                if ($request->get('direct')==1) {
                    //this is decrement general field
                    $product = $sell->product;
                    $product->general_quantity = $product->general_quantity - intval($sell_item['quantity']);
                    $product->save();
                }


                //this is product decrement from stock
                $product = $sell->product;
                $product->quantity = $product->quantity - intval($sell_item['quantity']);
                $product->save();


            }

            //discount
            $discount = $request->get('discount');
            $discountType = $request->get('discountType');
            $discountAmount = $discount;
            if($discountType == 'percentage'){
                $discountAmount = $total * (1 * $discount / 100);
            }

            $total_payable = $total - $discountAmount;
            //discount ends

            /*invoice tax*
            if(settings('invoice_tax') == 1){
                if(settings('invoice_tax_type') == 1){
                    $invoice_tax = (settings('invoice_tax_rate') * $total_payable) / 100;
                }else{
                    $invoice_tax = settings('invoice_tax_rate');
                }
            }else{
                $invoice_tax = 0;
            }


            */

            $invoice_tax = 0;

            if ($order_no=="0"){
                $transaction = new Transaction;
                $transaction->reference_no = $order_no_new;
                $transaction->client_id = $customer;
                $transaction->transaction_type = 'ORDER';
                $transaction->total_cost_price = $total_cost_price;
                $transaction->discount = $discountAmount;
                //saving total without product tax and shipping cost
                $transaction->total = $total_payable - $totalProductTax;
                $transaction->invoice_tax = round($invoice_tax, 2);
                $transaction->total_tax = round(($totalProductTax + $invoice_tax), 2);
                $transaction->labor_cost = $request->get('shipping_cost');
                $transaction->net_total = round(($total_payable + $request->get('shipping_cost') + $invoice_tax), 2);
                $transaction->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
                $transaction->paid = $paid;
                $transaction->user_id = $request->get('user_id');
                $transaction->save();

            }



            $transaction = new Transaction;
            $transaction->reference_no = $ref_no;
            $transaction->order_no = $order_no_new;

            $transaction->client_id = $customer;
            $transaction->transaction_type = 'sell';
            $transaction->total_cost_price = $total_cost_price;
            $transaction->discount = $discountAmount;
            //saving total without product tax and shipping cost
            $transaction->total = $total_payable - $totalProductTax;
            $transaction->invoice_tax = round($invoice_tax, 2);
            $transaction->total_tax = round(($totalProductTax + $invoice_tax), 2);
            $transaction->labor_cost = $request->get('shipping_cost');
            $transaction->net_total = round(($total_payable + $request->get('shipping_cost') + $invoice_tax), 2);
            $transaction->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
            $transaction->paid = $paid;
            $transaction->user_id = $request->get('user_id');
            $transaction->save();

            if($paid > 0){
                $payment = new Payment;
                $payment->client_id = $customer;
                $payment->amount = $paid;
                $payment->method = $request->get('method');
                $payment->type = 'credit';
                $payment->reference_no = $ref_no;
                $payment->note = "Paid for Invoice ".$ref_no;
                $payment->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
                $payment->user_id = $request->get('user_id');
                $payment->save();
            }
          $ref_no=  $transaction->reference_no;
        });

       // return response()->json(['message' => 'Successfully saved transaction.'], 200);
        return response()->json( Transaction::where('reference_no', $ref_no), 200);

     }

//Update sales Invoice
    public function update(Request $request, $id): JsonResponse
    {
        $customer = $request->get('customer');
        $order_no = $request->get('order_no');

        $enableProductTax = 0;

        if (!$customer) {
            throw new ValidationException('Customer ID is required.');
        }

        return  response()->json();
    }




    public  function details($id): JsonResponse
    {
        $query = Transaction::query();
        $query->where('id', $id);
        $query->with(['sells','sells.product']);
        $query->with(['payments']);
        $query->with(['client']);
        $query->with(['returnSales', 'returnSales.sells.product']);
        $AssociateArray = array('data' =>$query->get());

        return response()->json($AssociateArray  ,200);
    }


    public  function ReturnDetails($id): JsonResponse
    {
        $query = Transaction::query();
        $query->where('id', $id);
      //  $query->with(['sells','sells.product']);
      //  $query->with(['payments']);
        $query->with(['client']);
        $query->with(['returnSales', 'returnSales.sells.product']);
        $AssociateArray = array('data' =>$query->get());

        return response()->json($AssociateArray  ,200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function deleteSell(Request $request, Transaction $transaction) {

        $transaction = Transaction::findorFail($request->get('id'));

        foreach ($transaction->sells as $sell) {
            //add deleted product into stock
            $product = Product::find($sell->product_id);
            $current_stock = $product->quantity;
            $product->quantity = $current_stock + $sell->quantity;
            $product->save();

            //delete the sales entry in sells table
            $sell->delete();
        }

        foreach ($transaction->orders as $sell) {
            $sell->delete();
        }

        //delete all the payments against this transaction
        foreach($transaction->payments as $payment){
            $payment->delete();
        }

        //delete all the return sells against this transaction
        foreach($transaction->returnSales as $return){
            $return->delete();
        }

        //delete the transaction entry for this sale
        $transaction->delete();

        return response()->json( 'delete', 200);
    }

}
