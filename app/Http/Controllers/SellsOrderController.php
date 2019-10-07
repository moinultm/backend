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


class SellsOrderController extends Controller
{

    use helpers;
    use paginator;

    public function index(Request $request)
    {

    }

    public function show(Request $request)
    {

    }


    public function store(Request $request)
    {
        $customer = $request->get('customer');
        $enableProductTax = 0;

        if (!$customer) {
            throw new ValidationException('Customer ID is required.');
        }

        $ym = Carbon::now()->format('Y/m');

        $row = Representative::where('quantity' , '<','0')->withTrashed()->get()->count() > 0 ? Representative::where('quantity' , '>','0')->withTrashed()->get()->count() + 1 : 1;
        $ref_no = $ym.'/SO-'.self::ref($row);

        $totalProductTax = 0;
        $productTax = 0;

        $orders = $request->get('orders');
        $orders = json_decode($orders, TRUE);

        DB::transaction(function() use ($request , $orders, $ref_no, &$total, &$total_cost_price, &$totalProductTax, $customer,$productTax  ) {
            foreach ($orders as $order_item) {

                if (intval($order_item['quantity']) === 0) {
                    throw new ValidationException('Product quantity is required');
                }
                if (!$order_item['product_id'] || $order_item['product_id'] === '') {
                    throw new ValidationException('Product ID is required');
                }

                $total = $total + $order_item['item_total'];
                $total_cost_price = $total_cost_price + ($order_item['cost_price'] * $order_item['quantity']);

                $order = new Order();
                $order->reference_no = $ref_no;
                $order->product_id = $order_item['product_id'];
                $order->quantity = $order_item['quantity'];
                $order->product_discount_percentage = $order_item['product_discount_percentage'];
                $order->product_discount_amount = $order_item['product_discount_amount'];
                $order->unit_cost_price = $order_item['cost_price'];
                $order->sub_total = $order_item['subtotal']- $productTax;
                $order->client_id = $customer;
                $order->date = Carbon::parse($request->get('date'))->format('Y-m-d');
                $order->user_id = $request->get('user_id');

                $order->save();

            }
        });

        return response()->json( Order::where('reference_no', $ref_no), 200);

    }


    public function edit(Request $request)
    {

    }


    public function destroy(Request $request)
    {

    }


}
