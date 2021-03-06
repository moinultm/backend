<?php

namespace App\Http\Controllers;

use App\Client;
use App\Payment;
use App\ReturnTransaction;
use App\Sell;
use App\Transaction;
use Illuminate\Http\Request;
use App\Traits\Helpers;

use Carbon\Carbon;

use App\Exceptions\ValidationException;
use App\Traits\Paginator;
class SellReturnController extends Controller
{
    use helpers;
    use paginator;

        public   function index(){


        }

    public   function show(Transaction $transaction){

        return response()->json( "" ,200);

    }

    //Return Sell
    public function returnSell(Transaction $transaction)
    {
        $quantity = 0;
        foreach($transaction->sells as $sell){
            $quantity = $quantity + $sell->quantity;
        }
        if($quantity <= 0){
            $message = "No product in this sell is left to return";
            return response()->json($message  ,403);
        }else{


            $data=   compact('transaction');
            $AssociateArray = array(
                'data' =>array_values($data)
            );

            return response()->json( $AssociateArray ,200);
        }
    }


    public function store(Request $request)
    {
        $transactionId = $request->get('transaction_id');
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            return  response()->json('Transaction was not found.');
        }

        $previousTotal = $transaction->total;
        $previosInvoiceTax = $transaction->invoice_tax;
        //$previosProductTax = $transaction->total_tax - $previosInvoiceTax;

        $total = 0;
        $updatedCostPrice = 0;
        $total_product_tax = 0;
        $total_return_quantity = 0;

        $client = Client::find($transaction->client_id);
        $due = $client->transactions->sum('net_total') - $client->payments->where('type', 'credit')->sum('amount');

        $items = $request->get('items');
        $items = json_decode($items, TRUE);
//
     /*  if($transaction){
           return response()->json($transaction->sells, 200);
       }
    */

        foreach ($items as $sell_item) {
           // $returnQuantity = intval($request->get('quantity_'. $sell->id)) ?: 0;
            $returnQuantity = intval($sell_item['quantity_return']) ?: 0;

            $total_return_quantity += $returnQuantity;

            //new
            $unitProductTax =   $sell_item['product_tax'] /  $sell_item['quantity']  ;

            if ($returnQuantity === 0) {
                $total =  $total + $sell_item['sub_total']  ;
                $total_product_tax = $total_product_tax +  $sell_item['product_tax'];
                continue;
            }
            $returnUnitPrice = floatval( $sell_item['mrp'] );
          //  $returnUnitPrice = floatval($request->get('unit_price_'. $sell->id));

            $sellId =  $sell_item['sell_id'] ;
            //$sellId = $request->get('sell_'. $sell->id);


            $sell = Sell::find($sellId);

            if($returnQuantity > $sell->quantity){
                $warning = "Return Quantity (".$returnQuantity.") Can't be greater than the Selling Quantity (".$sell->quantity.")";
                throw new ValidationException($warning,403);
            }

            $updatedSellQuantity = $sell->quantity - $returnQuantity;
            $updatedProductTax = $unitProductTax * $updatedSellQuantity;
            $subTotal = $updatedSellQuantity * $returnUnitPrice;

            if($previosInvoiceTax > 1){
                if(settings('invoice_tax_type') == 1){
                    $return_tax_amount = (settings('invoice_tax_rate') * ($returnQuantity * $returnUnitPrice)) / 100;
                }else{
                    $return_tax_amount = settings('invoice_tax_rate');
                }
            }else{
                $return_tax_amount = 0;
            }

            $sell->quantity = $updatedSellQuantity;
            $sell->sub_total = $subTotal;
            $sell->product_tax = $updatedProductTax;
            $sell->save();

            //update the cost price to deduct from transaction table
            $updatedCostPrice += $returnQuantity * $sell->unit_cost_price;

            $product = $sell->product;
            $currentStock = $product->quantity;
            $product->quantity = $currentStock + $returnQuantity;

            $current_general_stock = $product->general_quantity;
            $product->general_quantity =$current_general_stock +$returnQuantity;

            $product->save();

            $total += $subTotal;
            $total_product_tax = $total_product_tax + $updatedProductTax;

            // Save Return statement
            $return = new ReturnTransaction;
            $return->sells_id = $sell->id;
            $return->client_id = $sell->client_id;
            $return->return_vat = $unitProductTax * $returnQuantity;
            $return->sells_reference_no = $sell->reference_no;
            $return->return_units = $returnQuantity;
            $return->return_amount = ($returnQuantity * $returnUnitPrice) + $return_tax_amount + ($unitProductTax * $returnQuantity);
            $return->returned_by = \Auth::user()->id;
            $return->save();
        }

        //invoice tax
        if($previosInvoiceTax > 1){
            if(settings('invoice_tax_type') == 1){
                $invoice_tax = (settings('invoice_tax_rate') * $total) / 100;
            }else{
                $invoice_tax = settings('invoice_tax_rate');
            }
        }else{
            $invoice_tax = 0;
        }
        //ends

        if($total_return_quantity <= 0){
            $quantityerror = "You Can't return Zero Quantity";
            throw new ValidationException($quantityerror,403);
           // return  response()->json($quantityerror,403);
        }

        //update transaction for this return
        $transaction->total = $total;
        $transaction->invoice_tax = $invoice_tax;
        $transaction->total_tax = $total_product_tax + $invoice_tax;
        $transaction->net_total = $total + $invoice_tax + $transaction->labor_cost + $total_product_tax;
        $transaction->total_cost_price = $transaction->total_cost_price - $updatedCostPrice;
        $transaction->return = true;
        $transaction->save();

        $diff = ( $previousTotal + $previosInvoiceTax /*+ $previosProductTax*/) - ($total + $invoice_tax + $total_product_tax);

        //if difference is greater than due amount then we need to return some money to the customer
        if ($diff > $due) {
            $payment = new Payment;
            $payment->client_id =  $client->id;
            $payment->amount =  $due < 0 ? $diff :  $diff - $due;
            $payment->method = 'cash';
            $payment->type = "return";
            $payment->reference_no = $transaction->reference_no;
            $payment->note = "Return for ".$transaction->reference_no;
            $payment->date = Carbon::now()->format('Y-m-d H:i:s');
            $payment->save();
        }
        return response()->json( 'ok', 200);
    }

}
