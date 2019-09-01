<?php

namespace App\Http\Controllers;

use App\Client;
use App\Traits\Paginator;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use Paginator;

    public function index(Request $request): JsonResponse
    {
        $query = Client::where('client_type', 'purchaser');
        return response()->json(self::paginate($query, $request), 200);
    }


    public function store(Request $request){

        $rules = [
            'full_name' => [
                'required',
                'max:255'
            ],
            'contact' => [
                'required',
                'min:9'

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
        $client->client_type = 'customer';
        $client->account_no = $request->get('account_no');
              $client->provious_due = $request->get('previous_due');

        $client->save();
        return response()->json( 'success', 200);

    }


    public function update(Request $request,$id){

        $rules = [
            'full_name' => [
                'required',
                'max:255'
            ],
            'contact' => [
                'required',
                'min:9'

            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $client = Client::where('id', $id)->first();;
        $client->full_name = ucwords($request->get('full_name'));
        $client->company_name = ucwords($request->get('company_name'));
        $client->email = $request->get('email');
        $client->contact = $request->get('contact');
        $client->address = $request->get('address');
        $client->client_type = 'customer';
        $client->account_no = $request->get('account_no');
        $client->provious_due = $request->get('previous_due');

        $client->save();
        return response()->json( 'success', 200);

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
