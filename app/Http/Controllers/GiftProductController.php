<?php

namespace App\Http\Controllers;

use App\GiftProduct;
use App\Representative;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\ValidationException;
use App\Traits\Paginator;
use App\Traits\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class GiftProductController extends Controller
{

    use helpers;
    use paginator;



    public function store(Request $request): JsonResponse
    {
        $customer = $request->get('customer');
        $enableProductTax = 0;


        $rules = [
            'customer' => [
                'required'
            ]
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }





        $ym = Carbon::now()->format('Y/m');

        $rowT = Transaction::where('transaction_type', 'gift')->withTrashed()->get()->count() > 0 ? Transaction::where('transaction_type', 'gift')->withTrashed()->get()->count() + 1 : 1;
        $ref_no = $ym.'/S-'.self::ref($rowT);
        $total = 0;
        $totalProductTax = 0;
        $productTax = 0;
        $total_cost_price = 0;

        $row = GiftProduct::where('quantity' , '>','0')->withTrashed()->get()->count() > 0 ? GiftProduct::where('quantity' , '>','0')->withTrashed()->get()->count() + 1 : 1;
        $refno_gift = $ym.'/SG-'.self::ref($row);



        $paid = floatval($request->get('paid')) ?: 0;

        $sells = $request->get('items');
        $sells = json_decode($sells, TRUE);
        // print_r($sells);

        DB::transaction(function() use ($request , $sells, $ref_no, &$total, &$total_cost_price, &$totalProductTax, $customer, $paid, $enableProductTax, $productTax,$refno_gift) {
            foreach ($sells as $sell_item) {

                if (intval($sell_item['quantity']) === 0) {
                    throw new ValidationException('Product quantity is required');
                }

                if (!$sell_item['product_id'] || $sell_item['product_id'] === '') {
                    throw new ValidationException('Product ID is required');
                }

                 $total_cost_price = $total_cost_price + ($sell_item['cost_price'] * $sell_item['quantity']);

                //main Table
                $sell = new GiftProduct();
                $sell->reference_no = $refno_gift;
                $sell->product_id = $sell_item['product_id'];
                $sell->quantity = $sell_item['quantity'];
                $sell->unit_cost_price = $sell_item['cost_price'];
                $sell->client_id = $customer;
                $sell->user_id = $request->get('user_id');
                $sell->date = Carbon::parse($request->get('date'))->format('Y-m-d');

                $sell->save();

                //Transaction Table

                }

        }); //end Transaction
        // return response()->json(['message' => 'Successfully saved transaction.'], 200);
        return response()->json( 'success', 200);
    }


}
