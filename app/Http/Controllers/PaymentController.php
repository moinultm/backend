<?php

namespace App\Http\Controllers;

use App\Client;
use App\Payment;
use App\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PaymentController extends Controller
{

    public function store(Request $request)
    {
        $user_id = $request->get('user_id');

        if($request->get('invoice_payment') == 1){
            //direct invoice-wise payment starts
            $ref_no = $request->get('reference_no');

            $transaction = Transaction::where('reference_no', $ref_no)->first();
            $previously_paid = $transaction->paid;
            $transaction->paid = round(($previously_paid + $request->get('amount')), 2);
            $transaction->save();

            //saving paid amount into payment table
            $payment = new Payment;
            $payment->client_id = $request->get('client_id');
            $payment->user_id = $request->get('user_id');
            $payment->amount = round($request->get('amount'),2);
            $payment->method = $request->get('method');
            $payment->type = $request->get('type');
            if($request->get('reference_no')){
                $payment->reference_no = $request->get('reference_no');
            }
            $payment->note = $request->get('note');
            $payment->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
            $payment->save();

        }else{
            //client-wise payment starts
            $amount = round($request->get('amount'), 2);
            $client_id = $request->get('client_id');
            $client = Client::find($client_id);

            foreach($client->transactions as $transaction){
                $due = round(($transaction->net_total - $transaction->paid), 2);
                $previously_paid = $transaction->paid;
                if($due >= 0 && $amount > 0){
                    if($amount > $due){
                        $restAmount = $amount - $due;
                        $transaction->paid = $due + $previously_paid;
                        $transaction->save();

                        //payment
                        $payment = new Payment;
                        $payment->client_id = $client_id;
                        $payment->user_id = $request->get('user_id');
                        $payment->amount = $due;
                        $payment->method = $request->get('method');
                        $payment->type = $request->get('type');
                        $payment->reference_no = $transaction->reference_no;

                        $payment->note = $request->get('note');
                        $payment->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
                        $payment->save();

                    }else{
                        $restAmount = 0;
                        $transaction->paid = $amount + $previously_paid;
                        $transaction->save();

                        //payment
                        $payment = new Payment;
                        $payment->client_id = $client_id;

                        $payment->amount = $amount;
                        $payment->method = $request->get('method');
                        $payment->type = $request->get('type');
                        $payment->reference_no = $transaction->reference_no;

                        $payment->note = $request->get('note');
                        $payment->date = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
                        $payment->save();
                    }

                    $amount = $restAmount;
                }
                if($amount <= 0){
                    break;
                }
            }
            //client-wise payment ends
        }

        $message = trans('core.payment_received');
        return response()->json('success', 200);
    }

}
