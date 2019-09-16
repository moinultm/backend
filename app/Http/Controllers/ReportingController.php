<?php

namespace App\Http\Controllers;

use App\Product;
use App\Purchase;
use App\Sell;
use App\Traits\Paginator;
use App\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\Helpers;

class ReportingController extends Controller
{
    use Paginator;
    use helpers;



    public function  productSummary(Request $request)
    {



        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');

        if($request->get('from') || $request->get('to')) {
            if(!is_null($from)){

                $from = Carbon::createFromFormat('Y-m-d',$from);
                $from = self::filterFrom($from);
                $products = Sell::whereBetween('date',[$from,$to])->get();
            //this wotks

            }else{
                $products = Sell::query();
                 $products->where('date','<=',$to);

            }
        }


        $AssociateArray = array(
            'data' =>  $products
        );

        return response()->json($AssociateArray ,200);
    }


    public function postProductReport(Request $request)
    {
        //get warehouse/branch name
        $warehouse_id = $request->get('warehouse_id');
        $warehouse_name = ($warehouse_id == 'all') ? 'All Branch' : Warehouse::where('id', $warehouse_id)->first()->name;

        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to = self::filterTo($to);

        $sells = ($warehouse_id == "all") ? Sell::query() : Sell::where('warehouse_id', $warehouse_id);
        $purchases = ($warehouse_id == "all") ? Purchase::query() : Purchase::where('warehouse_id', $warehouse_id);

        if($request->get('from') || $request->get('to')) {
            if(!is_null($from)){
                $from = Carbon::createFromFormat('Y-m-d',$from);
                $from = self::filterFrom($from);
                $sells->whereBetween('date',[$from,$to]);
                $purchases->whereBetween('date',[$from,$to]);
            }else{
                $sells->where('date','<=',$to);
                $purchases->where('date','<=',$to);
            }
        }

        $product_id = $request->get('product_id');
        if($product_id != 'all'){
            $products = Product::whereId($product_id)->get();
        }else{
            $products = Product::all();
        }

        $total = [];
        $total_profit = 0;
        foreach ($products as $product) {
            $cloneForSells = clone $sells;
            $cloneForPurchases = clone $purchases;

            $sellRow = $cloneForSells->whereProductId($product->id);
            if($sellRow->count() > 0){
                $sellItemMrp = $sellRow->first()->sub_total / $sellRow->first()->quantity;
                $sellItemCost = $sellRow->first()->unit_cost_price;
            }else{
                $sellItemMrp = 0;
                $sellItemCost = 0;
            }

            $total[$product->id]['name'] = $product->name;
            $total[$product->id]['purchase'] = $cloneForPurchases->whereProductId($product->id)->sum('quantity')." ".$product->unit;
            $total[$product->id]['sells'] = $sellRow->sum('quantity')." ".$product->unit;
            $total[$product->id]['stock'] = $product->quantity." ".$product->unit;

            //profit
            $total[$product->id]['profit'] = ($sellItemMrp - $sellItemCost) * $sellRow->sum('quantity')." ".settings('currency_code');

            $total_profit = $total_profit + floatval($total[$product->id]['profit']);
        }


        return view('reporting.productReport')
            ->withTotal($total)
            ->withFrom($request->get('from'))
            ->withTo($request->get('to'))
            ->with('total_profit',$total_profit)
            ->with('warehouse_name', $warehouse_name);

    }



}
