<?php

namespace App\Http\Controllers;

use App\Client;
use App\Traits\Helpers;
use App\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\Paginator;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    use Paginator;
    use helpers;

    public function index(Request $request): JsonResponse
    {
        $query = Client::query();
        //$query->where('client_type', 'purchaser');
        return response()->json(self::paginate($query, $request), 200);
    }


    public function store(Request $request)
    {
        $rules = [
            'full_name' => [
                'required',
                'max:255'
            ],
            'contact' => [
                'required',
                'min:9',
                'unique:clients,contact'
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
        $client->client_type = 'purchaser';
        $client->account_no = $request->get('account_no');

        if($request->get('previous_due') != null){
            $client->provious_due = $request->get('previous_due');
        }

        $client->save();

        if($request->get('previous_due') != null){
            $transaction = new Transaction;
            $row = Transaction::where('transaction_type', 'opening')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'opening')->withTrashed()->get()->count() + 1 : 1;
            $ref_no = "OPENING-".self::ref($row);
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


        return response()->json( 'success', 200);
    }


    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'full_name' => [
                'required',
                'max:255'
            ],
            'contact' => [
                'required',
                'min:9',
                'unique:clients,contact,'. $id
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $client = Client::where('id', $id)->first();
        $client->full_name = ucwords($request->get('full_name'));
        $client->company_name = ucwords($request->get('company_name'));
        $client->email = $request->get('email');
        $client->contact = $request->get('contact');
        $client->address = $request->get('address');
        $client->client_type = 'purchaser';
        $client->account_no = $request->get('account_no');

        if($request->get('previous_due') != null){
            $client->provious_due = $request->get('previous_due');
        }

        $client->save();

        if($request->get('previous_due') != null){
            $transaction = Client::where('client_id', $id)->first();
            $row = Transaction::where('transaction_type', 'opening')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'opening')->withTrashed()->get()->count() + 1 : 1;
            $ref_no = "OPENING-".self::ref($row);
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
        return response()->json( 'update success', 200);

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

}
