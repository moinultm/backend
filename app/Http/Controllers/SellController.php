<?php

namespace App\Http\Controllers;

use App\Payment;
use DB;
use App\Sell;
use App\Traits\Helpers;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Traits\Paginator;


class SellController extends Controller
{
    use helpers;
use paginator;


    public function index(Request $request)
    {
        $query = Sell::query();

        if($request->get('filter')) {
            $query= Sell::where(function($q) use($request) {
                $q->where('reference_no', 'LIKE', '%' . $request->get('filter') . '%');
            });
        }



        return response()->json(self::paginate($query, $request), 200);
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

                $total = $total + $sell_item['subtotal'];
                $total_cost_price = $total_cost_price + ($sell_item['cost_price'] * $sell_item['quantity']);

                $sell = new Sell;
                $sell->reference_no = $ref_no;
                $sell->product_id = $sell_item['product_id'];
                $sell->quantity = $sell_item['quantity'];
                $sell->product_discount_percentage = $sell_item['product_discount_percentage'];

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


}
