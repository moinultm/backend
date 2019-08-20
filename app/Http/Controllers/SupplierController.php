<?php

namespace App\Http\Controllers;

use App\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\Paginator;

class SupplierController extends Controller
{
    use Paginator;


    public function index(Request $request): JsonResponse
    {
        $query = Client::query();
        //$query->where('client_type', 'purchaser');
        return response()->json(self::paginate($query, $request), 200);
    }


    public function postPurchaser(Request $request)
    {
        $client->first_name = ucwords($request->get('first_name'));
        $client->last_name = ucwords($request->get('last_name'));
        $client->company_name = ucwords($request->get('company_name'));
        $client->email = $request->get('email');
        $client->phone = $request->get('phone');
        $client->address = $request->get('address');
        $client->client_type = 'purchaser';
        $client->account_no = $request->get('account_no');

        if($request->get('previous_due') != null){
            $client->provious_due = $request->get('previous_due');
        }

        $client->save();

        if($request->get('previous_due') != null){
            $transaction = new Transaction;
            $row = Transaction::where('transaction_type', 'opening')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'opening')->withTrashed()->get()->count() + 1 : 1;
            $ref_no = "OPENING-"Self::ref($row);
            $transaction->reference_no = $ref_no;
            $transaction->client_id = $client->id;
            $transaction->transaction_type = "opening";
            $transaction->warehouse_id = 1;
            $transaction->total = $request->get('previous_due');
            $transaction->invoice_tax = 0;
            $transaction->total_tax = 0;
            $transaction->labor_cost = 0;
            $transaction->net_total = $request->get('previous_due');
            $transaction->paid = 0;
            $transaction->save();

        }

        $message = trans('core.changes_saved');
        return redirect()->route('purchaser.index')->withSuccess($message);
    }
}
