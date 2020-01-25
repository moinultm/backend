<?php

namespace App\Http\Controllers;

use App\Client;
use App\Payment;
use App\Product;
use App\Purchase;
use App\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\Paginator;
use App\Traits\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{

    use helpers;
    use paginator;


    public function index(Request $request): JsonResponse
    {
        $suppliers = Client::orderBy('full_name', 'asc')->where('client_type', 'purchaser');

        $transactions = Transaction::where('transaction_type', 'purchase')->orderBy('date', 'desc');

        if($request->get('invoice')) {
            $transactions->where('reference_no', 'LIKE', '%' . $request->get('invoice') . '%');
        }

        if($request->get('supplier')) {
            $transactions->whereClientId($request->get('supplier'));
        }

        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to =self:: filterTo($to);

        if($request->get('from') || $request->get('to')) {
            if(!is_null($from)){
                $from = Carbon::createFromFormat('Y-m-d',$from);
                $from = self::filterFrom($from);
                $transactions->whereBetween('date',[$from,$to]);
            }else{
                $transactions->where('date','<=',$to);
            }
        }
       $transaction= self::paginate($transactions, $request);
        $suppliers=self::paginate($suppliers , $request);
         $data=   compact('transaction','suppliers');

        $AssociateArray = array(
            'data' =>  $data
        );

        return response()->json($transaction ,200);

     }

    public function show($id): JsonResponse
    {

        return response()->json('',200);


    }

    public function getLists(): JsonResponse

    {
        Transaction::$preventAttrSet=true;

        $transactions = Transaction::select('id','reference_no')->where('transaction_type', 'purchase')->orderBy('date', 'desc')->get();
        $AssociateArray = array(
            'data' =>  $transactions
        );
        return response()->json($AssociateArray ,200);
    }


    public function store(Request $request): JsonResponse
    {

        $supplier = $request->get('supplier');
        $enableProductTax =0;

        if (!$supplier) {
            throw new ValidationException('Please Select A Supplier');
        }

        $ym = Carbon::now()->format('Y/m');

        $row = Transaction::where('transaction_type', 'purchase')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'purchase')->withTrashed()->get()->count() + 1 : 1;
        $ref_no = 'PI-'.self::ref($row);
        $total = 0;
        $totalProductTax = 0;
        $productTax = 0;
        $purchases = $request->get('purchases');
        $purchases = json_decode($purchases, TRUE);

        $paid = floatval($request->get('paid')) ?: 0;

        DB::transaction(function() use ($request , $purchases, $ref_no, &$total, &$totalProductTax, $supplier, $paid, $enableProductTax, $productTax){
            foreach ($purchases as $purchase_item) {
                if (intval($purchase_item['quantity']) === 0) {
                    throw new ValidationException('Product quantity is required');
                }

                if (!$purchase_item['product_id'] || $purchase_item['product_id'] === '') {
                    throw new ValidationException('Product ID is required');
                }

                $total = $total + $purchase_item['subtotal'];
                $purchase = new Purchase;
                $purchase->reference_no = $ref_no;
                $purchase->product_id = $purchase_item['product_id'];
                $purchase->quantity = $purchase_item['quantity'];

                if($enableProductTax == 1){
                    //product tax calculation
                    $product_row = Product::findorFail($purchase_item['product_id']);
                    $taxRate = $product_row->tax->rate;
                    $taxType = $product_row->tax->type;

                    $productTax = ($taxType == 1) ? (($purchase_item['quantity'] * $taxRate * $purchase_item['price']) / 100) : ($purchase_item['quantity'] * $taxRate);

                    $purchase->product_tax = $productTax;
                    //ends
                    $totalProductTax = $totalProductTax + $productTax;
                }

                $purchase->sub_total = $purchase_item['subtotal'] - $productTax;
                $purchase->client_id = $supplier;
                $purchase->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
                $purchase->save();

                //Product Tables Quantity
                $product = $purchase->product;

                $product->quantity = $product->quantity + intval($purchase_item['quantity']);
                $product->general_quantity = $product->general_quantity + intval($purchase_item['quantity']);

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

            /*invoice tax
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

//using this untill fixx the settings var 11-9-19
            $invoice_tax = 0;
//using this untill fixx the settings var 11-9-19

            $transaction = new Transaction;
            $transaction->reference_no = $ref_no;
            $transaction->client_id = $request->get('supplier');
            $transaction->transaction_type = 'purchase';
            $transaction->discount = $discountAmount;
            $transaction->total = $total_payable - $totalProductTax;
            $transaction->invoice_tax = round($invoice_tax, 2);
            $transaction->total_tax = round(($totalProductTax + $invoice_tax), 2);
            $transaction->net_total = round(($total_payable + $invoice_tax), 2);
            $transaction->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
            $transaction->paid = $paid;
            $transaction->save();

            if($paid > 0){
                $payment = new Payment;
                $payment->client_id = $request->get('supplier');
                $payment->amount = $request->get('paid');
                $payment->method = $request->get('method');
                $payment->type = 'debit';
                $payment->reference_no = $ref_no;
                $payment->note = "Paid for bill ".$ref_no;
                $payment->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
                $payment->save();
            }
        });


        return response()->json( 'success', 200);
    }

    public  function details($id): JsonResponse
    {
        $query = Transaction::query();
        $query->where('id', $id);
        $query->with(['purchases','purchases.product']);
        $query->with(['payments']);
        $query->with(['client']);

        $AssociateArray = array('data' =>$query->get());

        return response()->json($AssociateArray  ,200);
    }


    public function update(Request $request, $id): JsonResponse
    {

    }

    public function deletePurchase(Request $request) {

        $transaction = Transaction::findorFail($request->get('id'));
        foreach ($transaction->purchases as $purchase) {
            //subtract deleted product from stock
            $product = Product::findorFail($purchase->product_id);

            $current_stock = $product->quantity;
            $product->quantity = $current_stock - $purchase->quantity;

            $current_general_stock = $product->general_quantity;

            $product->general_quantity =$current_general_stock - $purchase->quantity;
            $product->save();

            //delete the purchase entry in purchases table
            $purchase->delete();
        }

        //delete all the payments against this transaction
        foreach($transaction->payments as $payment){
            $payment->delete();
        }

        //delete the transaction entry for this sale
        $transaction->delete();

        $message = trans('core.deleted');
        return response()->json( 'delete', 200);
    }



    public function destroy(Request $request, int $id): JsonResponse
    {

    }


}

