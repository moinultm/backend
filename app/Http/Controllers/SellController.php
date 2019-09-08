<?php

namespace App\Http\Controllers;

use App\Client;
use App\Payment;
use DB;
use App\Sell;
use App\Traits\Helpers;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Traits\Paginator;
use Illuminate\Http\JsonResponse;


class SellController extends Controller
{
    use helpers;
use paginator;


    public function index(Request $request)
    {


        $customers =  Client::orderBy('full_name', 'asc')
            ->where('client_type', "!=" ,'purchaser')
            ->pluck('full_name', 'id');

        $transactions = Transaction::where('transaction_type', 'sell')->orderBy('date', 'desc') ;

        if($request->get('invoice_no')) {
            $transactions->where('reference_no', 'LIKE', '%' . $request->get('invoice_no') . '%');
        }

        if($request->get('customer')) {
            $transactions->whereClientId($request->get('customer'));
        }

        if($request->get('type') == 'pos') {
            $transactions->wherePos(1);
        }

        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to = self::filterTo($to);

        if($request->get('from') || $request->get('to')) {
            if(!is_null($from)){
                $from = Carbon::createFromFormat('Y-m-d',$from);
                $from = filterFrom($from);
                $transactions->whereBetween('date',[$from,$to]);
            }else{
                $transactions->where('date','<=',$to);
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

        $AssociateArray = array(
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


      //return response()->json( $AssociateArray , 200);
 return response()->json(self::paginate($transactions, $request), 200);
    }


    public function store(Request $request)
    {

        //we have disabled the taxes and settings checkup

        $customer = $request->get('customer');
        $enableProductTax = 0;

        if (!$customer) {
            throw new ValidationException('Customer ID is required.');
        }

        $ym = Carbon::now()->format('Y/m');

        $row = Transaction::where('transaction_type', 'sell')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'sell')->withTrashed()->get()->count() + 1 : 1;
        $ref_no = $ym.'/S-'.self::ref($row);
        $total = 0;
        $totalProductTax = 0;
        $productTax = 0;
        $total_cost_price = 0;

        $paid = floatval($request->get('paid')) ?: 0;

        $sells = $request->get('sells');
        $sells = json_decode($sells, TRUE);
       // print_r($sells);

        DB::transaction(function() use ($request , $sells, $ref_no, &$total, &$total_cost_price, &$totalProductTax, $customer, $paid, $enableProductTax, $productTax){
            foreach ($sells as $sell_item) {

                if (intval($sell_item['quantity']) === 0) {
                    throw new ValidationException('Product quantity is required');
                }

                if (!$sell_item['product_id'] || $sell_item['product_id'] === '') {
                    throw new ValidationException('Product ID is required');
                }

                $total = $total + $sell_item['item_total'];
                $total_cost_price = $total_cost_price + ($sell_item['cost_price'] * $sell_item['quantity']);

                $sell = new Sell;
                $sell->reference_no = $ref_no;
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

                $sell->unit_cost_price = $sell_item['cost_price'];
                $sell->sub_total = $sell_item['subtotal']- $productTax;
                $sell->client_id = $customer;
                $sell->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
                $sell->save();
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

            $transaction = new Transaction;
            $transaction->reference_no = $ref_no;
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
                $payment->save();
            }
        });

       // return response()->json(['message' => 'Successfully saved transaction.'], 200);
        return response()->json( 'success', 200);

     }


    public  function details($id): JsonResponse
    {
        $query = Transaction::query();
        $query->where('id', $id);
        $query->with(['sells','sells.product']);
        $query->with(['payments']);
        $query->with(['client']);
        $AssociateArray = array('data' =>$query->get());

        return response()->json($AssociateArray  ,200);
    }

}
