<?php

namespace App\Http\Controllers;

use App\Product;
use App\Purchase;
use App\Sell;
use App\Traits\Paginator;
use App\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use App\Traits\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportingController extends Controller
{
    use Paginator;
    use helpers;



    public function  productSummary(Request $request)
    {

        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');

        //


        $product= Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw('products.name')
            ->groupBy('products.name','products.mrp',
                               'sells.product_discount_percentage')->take(1);
        


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
        $products = Sell::query();

        $temp=$this->temporary_check( $from,$to);

        $AssociateArray = array(
            'data' =>  $products
        );

        return response()->json( $temp ,200);
    }


    public function temporary_check($from,$to )
    {

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->date('INV_DATE');
            $table->integer('TRAN_QUANTITY');
            $table->temporary();
        });


        $select = Product::query()
                 ->select(array('name','created_at','mrp'));

        $bindings = $select->getBindings();
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','INV_DATE','TRAN_QUANTITY'], $select);

        $data2 = DB::table('TEMP_OPENING')
            ->selectRaw('STOCK_ITEM_NAME')

            ->get();


     Schema::drop('TEMP_OPENING');
//////////////////////////////////////////////////////
        Schema::create('temp_transaction', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->date('INV_DATE');
            $table->integer('OPENING_QUANTITY');
            $table->integer('INWARD_QUANTITY');
            $table->integer('OUTWARD_QUANTITY');

            $table->temporary();
        });
/////////////////////////////////////////////////////
        DB::table('TEMP_TRANSACTION')->insert([
            'STOCK_ITEM_NAME'=>'A',
            'INV_DATE'=>'2019-08-16',
            'OPENING_QUANTITY'=>1,
            'INWARD_QUANTITY'=>0,
            'OUTWARD_QUANTITY'=>0
        ]);
 //////////////////////////////////////////////////////

        $data = DB::table('TEMP_TRANSACTION')
            ->selectRaw('STOCK_ITEM_NAME ,
             sum(OPENING_QUANTITY) as OPENING_QUANTITY,
             sum(INWARD_QUANTITY) as INWARD_QUANTITY,
             sum(OUTWARD_QUANTITY) as OUTWARD_QUANTITY')
            ->groupBy('STOCK_ITEM_NAME')
            ->where('INV_DATE','<=', $from)
            ->get();

        Schema::drop('TEMP_TRANSACTION');

        return $data2;
    }

//https://stackoverflow.com/questions/47493155/creating-temporary-table-in-laravel-lumen-and-insert-data


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
