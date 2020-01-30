<?php

namespace App\Http\Controllers;

use App\DamageProduct;
use App\Expense;
use App\GiftProduct;
use App\Payment;
use App\Product;
use App\Purchase;
use App\Representative;
use App\Sell;
use App\Traits\Paginator;
use App\Transaction;
use App\User;
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



    public function  productReport(Request $request)
    {

//need to fix dates query

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));


        $from = $request->get('from') ?:date('Y-m-d H:i:s');
        $to = $request->get('to')?:date('Y-m-d H:i:s');



        if(!is_null($from)) {
            $temp = $this->temporary_check($from, $to);
            }
            else{
                $temp = $this->temporary_check($nowDate, $nowDate);
            }

        $AssociateArray = array(
            'data' =>  $temp
        );


       return response()->json( $AssociateArray ,200);
    }


    public function temporary_check($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);

////////////////OPENING-PROCESS///////////////////////////

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->temporary();
        });


        $select1= Product::query()->select(array('name','opening_stock','opening_stock_value'));

      /*  $select2 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , COALESCE(sum(sells.quantity*-1),0)as Quantity,COALESCE(sum(sells.sub_total*-1),0)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.name' );*/

        //AS OPENING OUT , Rate not defined - 26-11-2019
        $select2= Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , sum(representatives_stock.quantity*-1)as Quantity')
            ->whereDate('date','<',$from)
            ->groupBy('products.name');


        $select3 = Purchase::query()
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , COALESCE(sum(purchases.quantity),0) as Quantity,COALESCE(sum(purchases.sub_total),0)as Amount')
            ->where('date','<',$from)
            ->groupBy('products.name' );

        $select4 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , COALESCE(sum(damage_products.quantity*-1),0)as Quantity,COALESCE(sum(damage_products.unit_cost_price),0)as Amount')
             ->where('date','<',$from)
            ->groupBy('products.name' );

        $select5 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , COALESCE(sum(gift_products.quantity*-1),0)as Quantity,COALESCE(sum(gift_products.unit_cost_price),0)as Amount')
             ->where('date','<',$from)
            ->groupBy('products.name' );


        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select1);
       DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY'], $select2);
       DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select3);
      DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select4);
       DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select5);

//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->integer('OUTWARD_QUANTITY')->default(0);
            $table->integer('OUTWARD_AMOUNT')->default(0);
            $table->integer('INWARD_QUANTITY')->default(0);
            $table->integer('INWARD_AMOUNT')->default(0);
            $table->integer('GIFT_QUANTITY')->default(0);
            $table->integer('DAMAGE_QUANTITY')->default(0);
            $table->temporary();
        });


        $select4= DB::table('TEMP_OPENING')
            ->selectRaw('STOCK_ITEM_NAME , COALESCE(sum(TRAN_QUANTITY),0) as TRAN_QUANTITY , COALESCE(sum(TRAN_AMOUNT) ,0)as TRAN_AMOUNT,0,0,0,0,0,0')
            ->groupBy('STOCK_ITEM_NAME' );

/*
        $select5 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,COALESCE(sum(sells.quantity*-1),0)as OUTWARD_QUANTITY,COALESCE(sum(sells.sub_total*-1),0)as AMOUNT,0,0,0,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );
*/

        //AS OPENING OUT , Rate not defined - 26-11-2019
        $select5= Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , sum(representatives_stock.quantity*-1)as OUTWARD_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->where('representatives_stock.quantity','>','0')
            ->groupBy('products.name');


        $select6 = Purchase::query()
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,COALESCE(sum(purchases.quantity),0)as INWARD_QUANTITY,COALESCE(sum(purchases.sub_total),0)as AMOUNT,0,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );


        $select7 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,0,0,COALESCE(sum(gift_products.quantity*-1),0)as GIFT_QUANTITY,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );


        $select8 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,0,0,0,COALESCE(sum(damage_products.quantity*-1),0)as DAMAGE_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select4);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME', 'OUTWARD_QUANTITY'], $select5);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select6);

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select7);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select8);


////////////////FINAL SELECTION//////////////////////////////

        $dataProduct = DB::table('TEMP_TRANSACTION')
            ->selectRaw('STOCK_ITEM_NAME , 
            sum(TRAN_QUANTITY) as TRAN_QUANTITY , 
            sum(TRAN_AMOUNT) as  TRAN_AMOUNT,
            sum(INWARD_QUANTITY) as INWARD_QUANTITY,
            sum(INWARD_AMOUNT) as  INWARD_AMOUNT,
            sum(OUTWARD_QUANTITY) as OUTWARD_QUANTITY,
              sum(OUTWARD_AMOUNT) as  OUTWARD_AMOUNT,
              sum(GIFT_QUANTITY) as GIFT_QUANTITY,
              sum(DAMAGE_QUANTITY) as DAMAGE_QUANTITY')
            ->groupBy('STOCK_ITEM_NAME' )
            ->orderBy('STOCK_ITEM_NAME')
            ->get();


        Schema::drop('TEMP_OPENING');
        Schema::drop('TEMP_TRANSACTION');

        return $dataProduct;
    }






    public function  productReportActual(Request $request)
    {

        //need to fix dates query

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));

        $from = $request->get('from') ?:date('Y-m-d H:i:s');
        $to = $request->get('to')?:date('Y-m-d H:i:s');

        if(!is_null($from)) {
            $temp = $this->temporary_check_actual($from, $to);
        }
        else{
            $temp = $this->temporary_check_actual($nowDate, $nowDate);
        }

        $AssociateArray = array(
            'data' =>  $temp
        );


        return response()->json( $AssociateArray ,200);
    }


    public function temporary_check_actual($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);

        ////////////////OPENING-PROCESS///////////////////////////

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->temporary();
        });


        $select1= Product::query()->select(array('name','opening_stock','opening_stock_value'));

        $select2 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , COALESCE(sum(sells.quantity*-1),0)as Quantity,COALESCE(sum(sells.sub_total*-1),0)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.name' );

        $select3 = Purchase::query()
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , COALESCE(sum(purchases.quantity),0) as Quantity,COALESCE(sum(purchases.sub_total),0)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.name' );

        $select4 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , COALESCE(sum(damage_products.quantity*-1),0)as Quantity,COALESCE(sum(damage_products.unit_cost_price),0)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.name' );

        $select5 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , COALESCE(sum(gift_products.quantity*-1),0)as Quantity,COALESCE(sum(gift_products.unit_cost_price),0)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.name' );


        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select1);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select2);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select3);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select4);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select5);

        //////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->integer('OUTWARD_QUANTITY')->default(0);
            $table->integer('OUTWARD_AMOUNT')->default(0);
            $table->integer('INWARD_QUANTITY')->default(0);
            $table->integer('INWARD_AMOUNT')->default(0);
            $table->integer('GIFT_QUANTITY')->default(0);
            $table->integer('DAMAGE_QUANTITY')->default(0);
            $table->temporary();
        });


        $select4= DB::table('TEMP_OPENING')
            ->selectRaw('STOCK_ITEM_NAME , COALESCE(sum(TRAN_QUANTITY),0) as TRAN_QUANTITY , COALESCE(sum(TRAN_AMOUNT) ,0)as TRAN_AMOUNT,0,0,0,0,0,0')
            ->groupBy('STOCK_ITEM_NAME' );


        $select5 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,COALESCE(sum(sells.quantity*-1),0)as OUTWARD_QUANTITY,COALESCE(sum(sells.sub_total*-1),0)as AMOUNT,0,0,0,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );

        $select6 = Purchase::query()
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,COALESCE(sum(purchases.quantity),0)as INWARD_QUANTITY,COALESCE(sum(purchases.sub_total),0)as AMOUNT,0,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );


        $select7 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,0,0,COALESCE(sum(gift_products.quantity*-1),0)as GIFT_QUANTITY,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );


        $select8 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,0,0,0,COALESCE(sum(damage_products.quantity*-1),0)as DAMAGE_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select4);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select5);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select6);

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select7);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select8);


////////////////FINAL SELECTION//////////////////////////////

        $dataProduct = DB::table('TEMP_TRANSACTION')
            ->selectRaw('STOCK_ITEM_NAME , 
            sum(TRAN_QUANTITY) as TRAN_QUANTITY , 
            sum(TRAN_AMOUNT) as  TRAN_AMOUNT,
            sum(INWARD_QUANTITY) as INWARD_QUANTITY,
            sum(INWARD_AMOUNT) as  INWARD_AMOUNT,
            sum(OUTWARD_QUANTITY) as OUTWARD_QUANTITY,
              sum(OUTWARD_AMOUNT) as  OUTWARD_AMOUNT,
              sum(GIFT_QUANTITY) as GIFT_QUANTITY,
              sum(DAMAGE_QUANTITY) as DAMAGE_QUANTITY')
            ->groupBy('STOCK_ITEM_NAME' )
            ->get();


        Schema::drop('TEMP_OPENING');
        Schema::drop('TEMP_TRANSACTION');

        return $dataProduct;
    }



//######################################::PRODUCT-REPORT-END::##########################################################


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
                $sells ->whereDate('date','<=',$to);
                $purchases ->whereDate('date','<=',$to);
            }
        }

        $product_id = $request->get('product_id');
        if($product_id != 'all'){
            $products = Product::whereId($product_id)->get();
        }else{
            $products = Product::all();
        }

        $total =array()  ;
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




    public function  representSummary(Request $request,$id)
    {
        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));

        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');


        if(!is_null($from)) {
            $temp = $this->represent_check($from, $to,$id);
        }
        else{
            $temp = $this->represent_check($nowDate, $nowDate,$id);
        }

        $AssociateArray = array(
            'data' =>  $temp
        );


        return response()->json( $AssociateArray ,200);

    }


    public function represent_check($from,$to,$id )
    {
        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);


////////////////OPENING-PROCESS///////////////////////////

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->temporary();
        });

        $select1= Product::query()->select(array('name'));

        //AS IN WARD
        $select2= Representative::query()
        ->join('products', 'representatives_stock.product_id', '=', 'products.id')
        ->selectRaw( 'products.name  , sum(representatives_stock.quantity)as Quantity')
        ->whereDate('date','<=',$from)
        ->where('user_id','=',$id)
        ->groupBy('products.name');

        //AS OUT WARD
        $select3= Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , sum(sells.quantity*-1)as Quantity,sum(sells.sub_total*-1)as Amount')
             ->whereDate('date','<=',$from)
            ->where('user_id','=',$id)
            ->groupBy('products.name');

        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME'], $select1);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY'], $select2);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select3);

//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->integer('OUTWARD_QUANTITY')->default(0);
            $table->integer('OUTWARD_AMOUNT')->default(0);
            $table->integer('INWARD_QUANTITY')->default(0);
            $table->integer('INWARD_AMOUNT')->default(0);
            $table->temporary();
        });


        $select4= DB::table('TEMP_OPENING')
            ->selectRaw('STOCK_ITEM_NAME , sum(TRAN_QUANTITY) as TRAN_QUANTITY , sum(TRAN_AMOUNT) as TRAN_AMOUNT,0,0,0,0')
            ->groupBy('STOCK_ITEM_NAME' );


        $select5 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,sum(sells.quantity*-1)as OUTWARD_QUANTITY,sum(sells.sub_total*-1)as AMOUNT,0,0')
            ->whereBetween('date',[$from,$to])
            ->where('user_id','=',$id)
            ->groupBy('products.name' );


        $select6 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,sum(representatives_stock.quantity)as INWARD_QUANTITY,0')
            ->whereBetween('date',[$from,$to])
            ->where('user_id','=',$id)
            ->where('representatives_stock.quantity','>',0)
            ->groupBy('products.name' );



        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT'], $select4);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT'], $select5);
         DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT'], $select6);


////////////////FINAL SELECTION//////////////////////////////

        $dataProduct = DB::table('TEMP_TRANSACTION')
            ->selectRaw('STOCK_ITEM_NAME , 
            sum(TRAN_QUANTITY) as TRAN_QUANTITY , 
            sum(TRAN_AMOUNT) as  TRAN_AMOUNT,
            sum(INWARD_QUANTITY) as INWARD_QUANTITY,
            sum(INWARD_AMOUNT) as  INWARD_AMOUNT,
            sum(OUTWARD_QUANTITY) as OUTWARD_QUANTITY,
              sum(OUTWARD_AMOUNT) as  OUTWARD_AMOUNT')
            ->groupBy('STOCK_ITEM_NAME' )
            ->get();


        Schema::drop('TEMP_OPENING');
        Schema::drop('TEMP_TRANSACTION');



        return $dataProduct;
    }


    public   function productSellReport(Request $request) {

        //get warehouse/branch name
        $warehouse_id = 'all';
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
                $sells ->whereDate('date','<=',$to);
                $purchases ->whereDate('date','<=',$to);
            }
        }

        $product_id ='all';

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
            $total[$product->id]['profit'] = ($sellItemMrp - $sellItemCost) * $sellRow->sum('quantity');

            $total_profit = $total_profit + floatval($total[$product->id]['profit']);
        }


        $query = compact( 'total');

        $array = json_decode($total, true);

        $AssociateArray = array(
            'data' => $total
        );

        //self::paginate()

        return response()->json($AssociateArray  ,200);

    }







    ////////////Stock General Report///////////////
    public  function stockGeneralReport(Request $request){
        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        try {
            if(!is_null($from)) {
                $temp = $this->summary_temp_check($from, $to);
            }
            else{
                $temp = $this->summary_temp_check($nowDate, $nowDate);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }


        return response()->json($temp ,200);
    }
    public function summary_temp_check($from,$to )
    {
        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);
        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);
////////////////OPENING-PROCESS///////////////////////////
        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->temporary();
        });
        $select0=Product::query()->select('id','opening_stock','opening_stock_value');
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select0);
        $select1 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.id , sum(representatives_stock.quantity*-1)as TRAN_QUANTITY,0')
            ->where('date','<',$from)
            ->where('representatives_stock.quantity','>',0)
            ->groupBy('products.id' );
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select1);
        // $select1= Product::query()->select(array('id','name','opening_stock','opening_stock_value'));
        $select2 = Sell::query()
            ->selectRaw( 'product_id AS STOCK_ITEM_ID ,sum(quantity*-1)as TRAN_QUANTITY,sum(sub_total*-1)as TRAN_AMOUNT')
            ->where('direct','=',1)
            ->where('date','<',$from)
            ->groupBy('STOCK_ITEM_ID');
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select2);
        $select3 = Purchase::query()
            ->selectRaw( 'product_id, sum(quantity)as TRAN_QUANTITY,sum(sub_total)as TRAN_AMOUNT')
            ->where('date','<',$from)
            ->groupBy('product_id' );
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select3);
        $select4 = DamageProduct::query()
            ->selectRaw( 'product_id AS STOCK_ITEM_ID, sum(quantity*-1) as TRAN_QUANTITY,sum(unit_cost_price)as TRAN_AMOUNT')
            ->where('date','<',$from)
            ->groupBy('STOCK_ITEM_ID'  );
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select4);
        $select5 = GiftProduct::query()
            ->selectRaw( 'product_id AS STOCK_ITEM_ID , sum(quantity*-1)as TRAN_QUANTITY,sum(unit_cost_price)as TRAN_AMOUNT')
            ->where('date','<',$from)
            ->groupBy('STOCK_ITEM_ID');
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select5);
        //DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select1);
//////////////////TRANSACTION-PROCESS//////////////////////////////
        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->integer('OUTWARD_QUANTITY')->default(0);
            $table->integer('OUTWARD_AMOUNT')->default(0);
            $table->integer('INWARD_QUANTITY')->default(0);
            $table->integer('INWARD_AMOUNT')->default(0);
            $table->integer('GIFT_QUANTITY')->default(0);
            $table->integer('GIFT_COST')->default(0);
            $table->integer('DAMAGE_QUANTITY')->default(0);
            $table->integer('DAMAGE_COST')->default(0);
            $table->temporary();
        });
        $select4= DB::table('TEMP_OPENING')
            ->selectRaw( 'STOCK_ITEM_ID , COALESCE(sum(TRAN_QUANTITY),0) as TRAN_QUANTITY , COALESCE(sum(TRAN_AMOUNT),0) as TRAN_AMOUNT')
            ->groupBy('STOCK_ITEM_ID');
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select4);
        $select9 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,sum(representatives_stock.quantity*-1)  as OUTWARD_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->where('representatives_stock.quantity','>',0)
            ->groupBy('products.id');
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','OUTWARD_QUANTITY'], $select9);
        $select5 = Sell::query()
            ->selectRaw( 'product_id AS STOCK_ITEM_ID,COALESCE(sum(sells.quantity*-1),0)as OUTWARD_QUANTITY,COALESCE(sum(sells.sub_total*-1),0)as OUTWARD_AMOUNT ')
            ->where('direct','=',1)
            ->whereBetween('date',[$from,$to])
            ->groupBy('product_id' );
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','OUTWARD_QUANTITY','OUTWARD_AMOUNT'], $select5);
        $select6 = Purchase::query()
            ->selectRaw( 'product_id,COALESCE(sum(purchases.quantity),0)as INWARD_QUANTITY,COALESCE(sum(purchases.sub_total),0)as INWARD_AMOUNT')
            ->whereBetween(DB::raw('DATE(date)'),[$from,$to])
            ->groupBy('product_id' );
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','INWARD_QUANTITY','INWARD_AMOUNT'], $select6);
        $select7 = GiftProduct::query()
            ->selectRaw( 'product_id,COALESCE(sum(gift_products.quantity*-1),0) as GIFT_QUANTITY, COALESCE(sum(gift_products.unit_cost_price*-1),0) as GIFT_COST')
            ->whereBetween(DB::raw('DATE(date)'),[$from,$to])
            ->groupBy('product_id' );
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','GIFT_QUANTITY','GIFT_COST'], $select7);
        $select8 = DamageProduct::query()
            ->selectRaw( 'product_id,COALESCE(sum(damage_products.quantity*-1),0)as DAMAGE_QUANTITY, COALESCE(sum(damage_products.unit_cost_price*-1),0) as DAMAGE_COST')
            ->whereBetween(DB::raw('DATE(date)'),[$from,$to])
            ->groupBy('product_id' );
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','DAMAGE_QUANTITY','DAMAGE_COST'], $select8);
////////////////FINAL SELECTION//////////////////////////////
        $select0= Product::query()
            ->leftJoin('TEMP_TRANSACTION','products.id','=','TEMP_TRANSACTION.STOCK_ITEM_ID')
            ->selectRaw('products.name as STOCK_ITEM_NAME,   products.mrp as ITEM_MRP,
            STOCK_ITEM_ID, 
            COALESCE(SUM(TRAN_QUANTITY),0) as TRAN_QUANTITY,         
           COALESCE( sum(TRAN_AMOUNT),0) as  TRAN_AMOUNT,
           COALESCE(  sum(INWARD_QUANTITY),0) as INWARD_QUANTITY,
            COALESCE( sum(INWARD_AMOUNT),0) as  INWARD_AMOUNT,
           COALESCE(  sum(OUTWARD_QUANTITY),0) as OUTWARD_QUANTITY,
            COALESCE( sum(OUTWARD_AMOUNT),0) as  OUTWARD_AMOUNT,
           COALESCE(  sum(GIFT_QUANTITY),0) as GIFT_QUANTITY,
            COALESCE( sum(GIFT_COST * GIFT_QUANTITY ),0) as GIFT_COST,
            COALESCE( sum(DAMAGE_QUANTITY),0) as DAMAGE_QUANTITY,
            COALESCE( sum(DAMAGE_COST*DAMAGE_QUANTITY),0) as DAMAGE_COST')
            ->groupBy('STOCK_ITEM_ID', 'STOCK_ITEM_NAME', 'ITEM_MRP')
            ->orderBy('STOCK_ITEM_ID')
            ->get();

        Schema::drop('TEMP_OPENING');
        Schema::drop('TEMP_TRANSACTION');

        $AssociateArray = array(
            'product' =>  $select0,

        );
        return $AssociateArray;
    }


//************************Report-Pivot General*********************************

    public  function stockGeneralReport2(Request $request){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');


        try {

            if(!is_null($from)) {
                $temp = $this->summary_temp_check2($from, $to);
            }
            else{
                $temp = $this->summary_temp_check2($nowDate, $nowDate);
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        }
      $summary=  $temp->select('TRAN_QUANTITY');

        $AssociateArray = array(
            'product' =>  $temp

        );


        return response()->json($AssociateArray ,200);
    }

    public function summary_temp_check2($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);


////////////////OPENING-PROCESS///////////////////////////

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->string('TRAN_TYPE');
            $table->temporary();
        });

        $select0=Product::query()->selectRaw('id,opening_stock,opening_stock_value,"Opening"');
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select0);

        $select1 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.id , sum(representatives_stock.quantity*-1)as TRAN_QUANTITY,0,"Opening"')
            ->where('date','<',$from)
            ->where('representatives_stock.quantity','>',0)
            ->groupBy('products.id' );

        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select1);

        // $select1= Product::query()->select(array('id','name','opening_stock','opening_stock_value'));

        $select2 = Sell::query()
            ->selectRaw( 'product_id AS STOCK_ITEM_ID ,sum(quantity*-1)as TRAN_QUANTITY,sum(sub_total*-1)as TRAN_AMOUNT,"Opening"')
            ->where('direct','=',1)
            ->where('date','<',$from)
            ->groupBy('STOCK_ITEM_ID');
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select2);


        $select3 = Purchase::query()
            ->selectRaw( 'product_id, sum(quantity)as TRAN_QUANTITY,sum(sub_total)as TRAN_AMOUNT,"Opening"')
            ->where('date','<',$from)
            ->groupBy('product_id' );
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select3);


        $select4 = DamageProduct::query()
            ->selectRaw( 'product_id AS STOCK_ITEM_ID, sum(quantity*-1) as TRAN_QUANTITY,sum(unit_cost_price)as TRAN_AMOUNT,"Opening"')
            ->where('date','<',$from)
            ->groupBy('STOCK_ITEM_ID');

        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select4);


        $select5 = GiftProduct::query()
            ->selectRaw( 'product_id AS STOCK_ITEM_ID , sum(quantity*-1)as TRAN_QUANTITY,sum(unit_cost_price)as TRAN_AMOUNT,"Opening"')
            ->where('date','<',$from)
            ->groupBy('STOCK_ITEM_ID');

        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select5);




        //DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select1);

//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->string('TRAN_TYPE');
            $table->temporary();
        });


        $select4= DB::table('TEMP_OPENING')
            ->selectRaw( 'STOCK_ITEM_ID , COALESCE(sum(TRAN_QUANTITY),0) as TRAN_QUANTITY , COALESCE(sum(TRAN_AMOUNT),0) as TRAN_AMOUNT,TRAN_TYPE')
            ->groupBy('STOCK_ITEM_ID','TRAN_TYPE');
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select4);



        $select9 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,sum(representatives_stock.quantity*-1)  as OUTWARD_QUANTITY,"Stock-Out"')
            ->whereBetween('date',[$from,$to])
            ->where('representatives_stock.quantity','>',0)
            ->groupBy('products.id');

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_TYPE'], $select9);


        $select5 = Sell::query()
            ->selectRaw( 'product_id AS STOCK_ITEM_ID,COALESCE(sum(sells.quantity*-1),0)as TRAN_QUANTITY,COALESCE(sum(sells.sub_total*-1),0)as TRAN_AMOUNT,"Stock-Out" ')
            ->where('direct','=',1)
            ->whereBetween('date',[$from,$to])
            ->groupBy('product_id' );
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select5);


        $select6 = Purchase::query()
            ->selectRaw( 'product_id,COALESCE(sum(purchases.quantity),0)as TRAN_QUANTITY,COALESCE(sum(purchases.sub_total),0)as TRAN_AMOUNT,"Stock-In"')
            ->whereBetween(DB::raw('DATE(date)'),[$from,$to])
            ->groupBy('product_id' );
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select6);


        $select7 = GiftProduct::query()
            ->selectRaw( 'product_id,COALESCE(sum(gift_products.quantity*-1),0) as TRAN_QUANTITY, COALESCE(sum(gift_products.unit_cost_price*-1),0) as TRAN_AMOUNT,"Product-Gift"')
            ->whereBetween(DB::raw('DATE(date)'),[$from,$to])
            ->groupBy('product_id' );
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select7);


        $select8 = DamageProduct::query()
            ->selectRaw( 'product_id,COALESCE(sum(damage_products.quantity*-1),0)as TRAN_QUANTITY, COALESCE(sum(damage_products.unit_cost_price*-1),0) as TRAN_AMOUNT,"Product-Damage"')
            ->whereBetween(DB::raw('DATE(date)'),[$from,$to])
            ->groupBy('product_id' );
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT','TRAN_TYPE'], $select8);


////////////////FINAL SELECTION//////////////////////////////

        $select0= Product::query()
            ->leftJoin('TEMP_TRANSACTION','products.id','=','TEMP_TRANSACTION.STOCK_ITEM_ID')
            ->selectRaw('products.name as Name,   products.mrp as ITEM_MRP,
            STOCK_ITEM_ID, 
            COALESCE(SUM(TRAN_QUANTITY),0) as TRAN_QUANTITY,         
           COALESCE( sum(TRAN_AMOUNT),0) as  TRAN_AMOUNT,        
            TRAN_TYPE as Transaction')
            ->groupBy('STOCK_ITEM_ID', 'Name', 'ITEM_MRP','Transaction')
            ->orderBy('STOCK_ITEM_ID')
            ->get();


        Schema::drop('TEMP_OPENING');
        Schema::drop('TEMP_TRANSACTION');

        return $select0;
    }

//*******************************STOCK REPORT- IN BASED**********************************
    public  function stockInReport(Request $request){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');


        //this for returning blank


        if(!is_null($from)) {
            $temp = $this->stock_In_report_temp_check($from, $to);
        }
        else{
            $temp = $this->stock_In_report_temp_check($nowDate, $nowDate);
        }


        $users = Transaction::where('transaction_type', 'purchase');

        //$users= Purchase::query()->select('id','reference_no','product_id','date');

        $products= Product::query()->select('id','name');

        $AssociateArray = array(
            'products' =>  $products->get(),
            'users'=>$users->get(),
            'crossData'=> $temp
        );

        return response()->json($AssociateArray ,200);
    }

    public function stock_In_report_temp_check($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);


//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->date('TRAN_DATE');
            $table->string('REF_NO');
            $table->integer('INWARD_QUANTITY')->default(0);
            $table->integer('INWARD_AMOUNT')->default(0);

            $table->temporary();
        });


        $select0= Product::query()->select(array('id','name','mrp'));
        //DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','ITEM_MRP'], $select0);

        $select5 = Purchase::query()
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,purchases.date,purchases.reference_no,sum(purchases.quantity)as INWARD_QUANTITY,sum(purchases.sub_total)as INWARD_AMOUNT')
            ->whereBetween('date',[$from,$to])

            ->groupBy('products.id','purchases.reference_no','purchases.date');

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_DATE','REF_NO','INWARD_QUANTITY','INWARD_AMOUNT'], $select5);



////////////////FINAL SELECTION//////////////////////////////

        $select0= Product::query()
            ->leftJoin('TEMP_TRANSACTION','products.id','=','TEMP_TRANSACTION.STOCK_ITEM_ID')
            ->selectRaw('STOCK_ITEM_ID, products.name as Name,   TRAN_DATE,REF_NO,   products.mrp as ITEM_MRP,
            TRAN_DATE as Date, 
            REF_NO,
            COALESCE(  sum(INWARD_QUANTITY),0) as INWARD_QUANTITY,
            COALESCE( sum(INWARD_AMOUNT),0) as  INWARD_AMOUNT' )
            ->groupBy('STOCK_ITEM_ID','Date','REF_NO', 'Name', 'ITEM_MRP')
            ->get();


         Schema::drop('TEMP_TRANSACTION');

        return $select0;

    }

//*******************************STOCK REPORT- IN BASED**********************************


//*******************************STOCK REPORT- OUT BASED**********************************
    public  function stockOutReport(Request $request){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');


        //this for returning blank
        if(!is_null($from)) {
            $temp = $this->stock_Out_report_temp_check($from, $to);
        }
        else{
            $temp = $this->stock_Out_report_temp_check($nowDate, $nowDate);
        }

        $users=   Transaction::select(DB::raw('DATE(date)as date'))->whereIn('transaction_type',['sell','transfer'])
            ->whereBetween('date',[$from,$to])
            ->distinct();
       // $users= Sell::query()->select('id','reference_no','product_id','date');
        $products= Product::query()->select('id','name');
            //skipped due to use of pivot table 16-1-19
        $AssociateArray = array(
           // 'products' =>  $products->get(),
           // 'users'=>$users->get(['date']),
            'crossData'=> $temp
        );

        return response()->json($AssociateArray ,200);
    }

    public function stock_Out_report_temp_check($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);

        //////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->date('TRAN_DATE');
            $table->string('REF_NO');
            $table->integer('OUTWARD_QUANTITY')->default(0);
            $table->integer('OUTWARD_AMOUNT')->default(0);
            $table->temporary();
        });




        $select1 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,representatives_stock.date,representatives_stock.ref_no , sum(representatives_stock.quantity)as OUTWARD_QUANTITY,0')
            ->whereBetween('date',[$from,$to])
            ->where('representatives_stock.quantity','>',0)
            ->groupBy('products.id','representatives_stock.date','representatives_stock.ref_no');

        $select5 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,sells.date,sells.reference_no,sum(sells.quantity)as OUTWARD_QUANTITY,sum(sells.sub_total)as OUTWARD_AMOUNT')
            ->where('direct','=',1)
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','sells.reference_no','sells.date');

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_DATE','REF_NO','OUTWARD_QUANTITY','OUTWARD_AMOUNT'], $select1);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_DATE','REF_NO','OUTWARD_QUANTITY','OUTWARD_AMOUNT'], $select5);

////////////////FINAL SELECTION//////////////////////////////

        $select0= Product::query()
            ->leftJoin('TEMP_TRANSACTION','products.id','=','TEMP_TRANSACTION.STOCK_ITEM_ID')
            ->selectRaw('STOCK_ITEM_ID, products.name as Name,   TRAN_DATE,  products.mrp as ITEM_MRP,
            TRAN_DATE as Date,          
            COALESCE(sum(OUTWARD_QUANTITY),0) as OUTWARD_QUANTITY,
            COALESCE(sum(OUTWARD_AMOUNT),0) as  OUTWARD_AMOUNT' )
            ->groupBy('STOCK_ITEM_ID','Date', 'Name', 'ITEM_MRP')
            ->orderBy('STOCK_ITEM_ID')
            ->get();


        Schema::drop('TEMP_TRANSACTION');

        return $select0;

    }

//*******************************STOCK REPORT- OUT BASED**********************************








//*******************************STOCK REPORT- USER BASED**********************************
    public  function stockReport2(Request $request){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');


        //this for returning blank


        if(!is_null($from)) {
            $temp = $this->stock_report_temp_check2($from, $to);
        }
        else{
            $temp = $this->stock_report_temp_check2($nowDate, $nowDate);
        }


        $users= User::query()->select('id','name');
        $products= Product::query()->select('id','name');
//select(DB::raw('DATE(date)as date'))->


        $AssociateArray = array(
            'products' =>  $products->get(),
            'users'=>$users->get(),
            'crossData'=> $temp,

        );

        return response()->json($AssociateArray ,200);
    }

    public function stock_report_temp_check2($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);

        //////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->string('USER_NAME');
            $table->integer('OUTWARD')->default(0);
            $table->string('TRAN_TYPE');
            $table->temporary();
        });


        $select5 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->join('users', 'users.id', '=', 'sells.user_id')
            ->selectRaw( 'products.id,users.name,sells.quantity as OUTWARD,"Quantity"')
            ->whereBetween('date',[$from,$to])
            ->groupBy('users.name','products.id','sells.quantity');
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','USER_NAME','OUTWARD','TRAN_TYPE'], $select5);


        $select7 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->join('users', 'users.id', '=', 'sells.user_id')
            ->selectRaw( 'products.id,users.name,sells.sub_total as OUTWARD,"Amount"' )
            ->whereBetween('date',[$from,$to])
            ->groupBy('users.name','products.id','sells.sub_total');
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','USER_NAME','OUTWARD','TRAN_TYPE'], $select7);


        $select6 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->join('users', 'users.id', '=', 'sells.user_id')
            ->selectRaw( 'products.id,users.name,sum(sells.quantity) as OUTWARD_QUANTITY,sum(sells.sub_total)as OUTWARD_AMOUNT, "Quantity"' )
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','users.name');

       // DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','USER_NAME','OUTWARD_QUANTITY','OUTWARD_AMOUNT','TRAN_TYPE'], $select6);


        ////////////////FINAL SELECTION//////////////////////////////

        $select0= Product::query()
            ->leftJoin('TEMP_TRANSACTION','products.id','=','TEMP_TRANSACTION.STOCK_ITEM_ID')
            ->selectRaw('products.name as STOCK_ITEM_NAME,USER_NAME,STOCK_ITEM_ID,     
                       COALESCE( OUTWARD,0) as  OUTWARD,TRAN_TYPE')
            ->groupBy('STOCK_ITEM_ID','USER_NAME', 'STOCK_ITEM_NAME','OUTWARD','TRAN_TYPE')
            ->get();


        Schema::drop('TEMP_TRANSACTION');
        return $select0;

    }

//*******************************STOCK REPORT- USER BASED 2 **********************************



//*******************************STOCK REPORT- USER BASED**********************************
    public  function stockReport(Request $request){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        //this for returning blank
        if(!is_null($from)) {
            $temp = $this->stock_report_temp_check($from, $to);
        }
        else{
            $temp = $this->stock_report_temp_check($nowDate, $nowDate);
        }


        $users= User::query()->select('id','name');
        $products= Product::query()->select('id','name');

        $sells= Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,  sum(sells.quantity)as Quantity,sum(sells.sub_total)as Amount')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id' );;

        $sells2= Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'sells.user_id, sum(sells.quantity)as Quantity,sum(sells.sub_total)as Amount')
            ->whereBetween('date',[$from,$to])
            ->groupBy('sells.user_id' );;

        $AssociateArray = array(
            'products' =>  $products->get(),
            'users'=>$users->get(),
            'crossData'=> $temp,
            'char'=>$sells->get(),
            'charUser'=>$sells2->get()
        );

        return response()->json($AssociateArray ,200);
    }

    public function stock_report_temp_check($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);


////////////////OPENING-PROCESS///////////////////////////

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('USER_ID')->default(0);
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->temporary();
        });


        $select1 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,representatives_stock.user_id  , sum(representatives_stock.quantity)as Quantity,0')
            ->where('date','<',$from)
            ->where('representatives_stock.quantity','>',0)
            ->groupBy('products.id','representatives_stock.user_id' );

       // DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','USER_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select1);


        $select2 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.id, sells.user_id, sum(sells.quantity*-1)as Quantity,sum(sells.sub_total*-1)as Amount')
            ->where('date','<',$from)
            ->groupBy('products.id','sells.user_id' );
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','USER_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select2);


        $select3 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,damage_products.user_id  , sum(damage_products.quantity*-1)as Quantity,sum(damage_products.unit_cost_price)as Amount')
            ->where('date','<',$from)

            ->groupBy('products.id','damage_products.user_id');
       // DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','USER_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select3);


        $select4 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,gift_products.user_id, sum(gift_products.quantity*-1)as Quantity,sum(gift_products.unit_cost_price)as Amount')
            ->whereDate('date','<',$from)
            ->groupBy('products.id','gift_products.user_id');
       // DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','USER_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select4);


//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('USER_ID');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->integer('OUTWARD_QUANTITY')->default(0);
            $table->integer('OUTWARD_AMOUNT')->default(0);
            $table->integer('INWARD_QUANTITY')->default(0);
            $table->integer('INWARD_AMOUNT')->default(0);
            $table->integer('GIFT_QUANTITY')->default(0);
            $table->integer('GIFT_COST')->default(0);
            $table->integer('DAMAGE_QUANTITY')->default(0);
            $table->integer('DAMAGE_COST')->default(0);
            $table->temporary();
        });




        $select4= DB::table('TEMP_OPENING')
            ->selectRaw( 'STOCK_ITEM_ID,USER_ID,sum(TRAN_QUANTITY) as TRAN_QUANTITY, sum(TRAN_AMOUNT) as TRAN_AMOUNT')
            ->groupBy('STOCK_ITEM_ID','USER_ID');

        //on 12-01-2020 disabled
      //DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','USER_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select4);

        $select5 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,sells.user_id,sum(sells.quantity*-1)as OUTWARD_QUANTITY,sum(sells.sub_total)as OUTWARD_AMOUNT')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','sells.user_id');

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','USER_ID','OUTWARD_QUANTITY','OUTWARD_AMOUNT'], $select5);



        $select6 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,representatives_stock.user_id,sum(representatives_stock.quantity*-1)  as OUTWARD_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->where('representatives_stock.quantity','>',0)
            ->groupBy('products.id','representatives_stock.user_id');
      //  DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','USER_ID','INWARD_QUANTITY'], $select6);


        $select7 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,gift_products.user_id,sum(gift_products.quantity*-1)as GIFT_QUANTITY, sum(gift_products.unit_cost_price*-1) as GIFT_COST')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','gift_products.user_id');
       // DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','USER_ID','GIFT_QUANTITY','GIFT_COST'], $select7);


        $select8 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,damage_products.user_id,sum(damage_products.quantity*-1)as DAMAGE_QUANTITY, sum(damage_products.unit_cost_price*-1) as DAMAGE_COST')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','damage_products.user_id');
       // DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','USER_ID','DAMAGE_QUANTITY','DAMAGE_COST'], $select8);


////////////////FINAL SELECTION//////////////////////////////
///
        $select0= Product::query()
            ->leftJoin('TEMP_TRANSACTION','products.id','=','TEMP_TRANSACTION.STOCK_ITEM_ID')
            ->selectRaw('products.name as STOCK_ITEM_NAME,   USER_ID,   products.mrp as ITEM_MRP,
            STOCK_ITEM_ID, 
            COALESCE(SUM(TRAN_QUANTITY),0) as TRAN_QUANTITY,         
           COALESCE( sum(TRAN_AMOUNT),0) as  TRAN_AMOUNT,
           COALESCE(  sum(INWARD_QUANTITY),0) as INWARD_QUANTITY,
            COALESCE( sum(INWARD_AMOUNT),0) as  INWARD_AMOUNT,
           COALESCE(  sum(OUTWARD_QUANTITY),0) as OUTWARD_QUANTITY,
            COALESCE( sum(OUTWARD_AMOUNT),0) as  OUTWARD_AMOUNT,
           COALESCE(  sum(GIFT_QUANTITY),0) as GIFT_QUANTITY,
            COALESCE( sum(GIFT_COST * GIFT_QUANTITY ),0) as GIFT_COST,
            COALESCE( sum(DAMAGE_QUANTITY),0) as DAMAGE_QUANTITY,
            COALESCE( sum(DAMAGE_COST*DAMAGE_QUANTITY),0) as DAMAGE_COST')
            ->groupBy('STOCK_ITEM_ID','USER_ID', 'STOCK_ITEM_NAME', 'ITEM_MRP')
            ->get();



        Schema::drop('TEMP_OPENING');
        Schema::drop('TEMP_TRANSACTION');


        return $select0;

    }

//*******************************STOCK REPORT- USER BASED**********************************






//*******************************Challan REPORT- USER BASED**********************************
    public  function challanReport(Request $request,$user){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');

        $userId = $user;

        //this for returning blank

        if(!is_null($from)) {
            $temp = $this->challan_report_temp_check($from, $to);
        }
        else{
            $temp = $this->challan_report_temp_check($nowDate, $nowDate);
        }


      //  $users= User::query()->select('id','name');

        $products= Product::query()->select('id','name');

        $queryN = Representative:: query()->select('ref_no','user_id','users.name')
            ->leftJoin('users' , 'users.id','=','representatives_stock.user_id')
            ->where('quantity', '>=', '0')
            ->groupBy('ref_no','user_id','users.name')
            ->orderBy('ref_no', 'DESC');

        $query = Transaction::where('transaction_type', 'transfer');

        if ($userId=='0'){
            $query->get();
        } else
        {
            $query->where('user_id','=', $userId);
        }



        $AssociateArray = array(
            'products' =>  $products->get(),
            'users'=>$query->get(),
            'crossData'=> $temp
        );

        return response()->json($AssociateArray ,200);
    }

    public function challan_report_temp_check($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);





//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->string('REF_NO');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('USER_ID');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->temporary();
        });


        $select6 = Representative::query()
            ->select(  'ref_no','product_id as STOCK_ITEM_ID','user_id as USER_ID', 'quantity as TRAN_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->where('quantity','>',0)
            ->groupBy( 'ref_no','product_id','user_id','quantity');

        DB::table('TEMP_TRANSACTION')->insertUsing(['REF_NO','STOCK_ITEM_ID','USER_ID','TRAN_QUANTITY'], $select6);


////////////////FINAL SELECTION//////////////////////////////

        $select0=   DB::table('TEMP_TRANSACTION')
            ->selectRaw('REF_NO,USER_ID, STOCK_ITEM_ID,TRAN_QUANTITY')
            ->groupBy('REF_NO','USER_ID','STOCK_ITEM_ID','TRAN_QUANTITY')
            ->get();


        Schema::drop('TEMP_TRANSACTION');

        return $select0;

    }

//*******************************Challan REPORT- USER BASED**********************************




//*******************************Damage REPORT- USER BASED**********************************
    public  function damageReport(Request $request,$user){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');

        $userId = $user;

        //this for returning blank

        if(!is_null($from)) {
            $temp = $this->damage_report_temp_check($from, $to);
        }
        else{
            $temp = $this->damage_report_temp_check($nowDate, $nowDate);
        }


        //  $users= User::query()->select('id','name');

        $products= Product::query()->select('id','name');

        $query = DamageProduct::query()->select('reference_no','user_id','users.name')
            ->leftJoin('users' , 'users.id','=','damage_products.user_id')
            ->where('quantity', '>=', '0')
            ->groupBy('reference_no','user_id','users.name')
            ->orderBy('reference_no', 'DESC');


        if ($userId=='0'){ return '';  } else
        {
            $query->where('user_id','=', $userId);
        }



        $AssociateArray = array(
            'products' =>  $products->get(),
            'users'=>$query->get(),
            'crossData'=> $temp
        );

        return response()->json($AssociateArray ,200);
    }

    public function damage_report_temp_check($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);





//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->string('REF_NO');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('USER_ID');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->temporary();
        });


        $select6 = DamageProduct::query()
            ->select(  'reference_no','product_id as STOCK_ITEM_ID','user_id as USER_ID', 'quantity as TRAN_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->where('quantity','>',0)
            ->groupBy( 'reference_no','product_id','user_id','quantity');

        DB::table('TEMP_TRANSACTION')->insertUsing(['REF_NO','STOCK_ITEM_ID','USER_ID','TRAN_QUANTITY'], $select6);


////////////////FINAL SELECTION//////////////////////////////

        $select0=   DB::table('TEMP_TRANSACTION')
            ->selectRaw('REF_NO,USER_ID, STOCK_ITEM_ID,TRAN_QUANTITY')
            ->groupBy('REF_NO','USER_ID','STOCK_ITEM_ID','TRAN_QUANTITY')
            ->get();


        Schema::drop('TEMP_TRANSACTION');

        return $select0;

    }

//*******************************Damage REPORT- USER BASED**********************************




//*******************************Damage REPORT- USER BASED**********************************
    public  function giftReport(Request $request,$user){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');

        $userId = $user;

        //this for returning blank

        if(!is_null($from)) {
            $temp = $this->gift_report_temp_check($from, $to);
        }
        else{
            $temp = $this->gift_report_temp_check($nowDate, $nowDate);
        }


        //  $users= User::query()->select('id','name');

        $products= Product::query()->select('id','name');

        $query = GiftProduct::query()->select('reference_no','user_id','users.name')
            ->leftJoin('users' , 'users.id','=','gift_products.user_id')
            ->where('quantity', '>=', '0')
            ->groupBy('reference_no','user_id','users.name')
            ->orderBy('reference_no', 'DESC');

        if ($userId=='0'){ return '';  } else
        {
            $query->where('user_id','=', $userId);
        }

        $AssociateArray = array(
            'products' =>  $products->get(),
            'users'=>$query->get(),
            'crossData'=> $temp
        );

        return response()->json($AssociateArray ,200);
    }

    public function gift_report_temp_check($from,$to )
    {

        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);



//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->string('REF_NO');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('USER_ID');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->temporary();
        });


        $select6 = GiftProduct::query()
            ->select(  'reference_no','product_id as STOCK_ITEM_ID','user_id as USER_ID', 'quantity as TRAN_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->where('quantity','>',0)
            ->groupBy( 'reference_no','product_id','user_id','quantity');

        DB::table('TEMP_TRANSACTION')->insertUsing(['REF_NO','STOCK_ITEM_ID','USER_ID','TRAN_QUANTITY'], $select6);


////////////////FINAL SELECTION//////////////////////////////

        $select0=   DB::table('TEMP_TRANSACTION')
            ->selectRaw('REF_NO,USER_ID, STOCK_ITEM_ID,TRAN_QUANTITY')
            ->groupBy('REF_NO','USER_ID','STOCK_ITEM_ID','TRAN_QUANTITY')
            ->get();


        Schema::drop('TEMP_TRANSACTION');

        return $select0;

    }

//*******************************GiftREPORT- USER BASED**********************************







    // purchase Status Report
    public function postPurchaseReport(Request $request)
    {

        $warehouse_id = 'all';
        $query = Transaction::where('transaction_type', 'purchase');
        $transactions = ($warehouse_id == 'all') ? $query : $query->where('warehouse_id', $warehouse_id );
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to = self:: filterTo($to);

        if($request->get('from') || $request->get('to')) {
            if(!is_null($from)){
                $from = Carbon::createFromFormat('Y-m-d',$from);
                $from =  self::filterFrom($from);
                $transactions->whereBetween('date',[$from,$to]);
            }else{
                $transactions ->whereDate('date','<=',$to);
            }
        }


        $transactions->with(['purchases.product']);

        $AssociateArray = array(
            'data' => $transactions->get()
        );



        return response()->json($AssociateArray ,200);


    }

    public  function postSellsReport(Request $request){

        $warehouse_id = 'all';
        $warehouse_name = ($warehouse_id == 'all') ? 'All Branch' : Warehouse::where('id', $warehouse_id);
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to = self::filterTo($to);


        $query = Transaction::where('transaction_type', 'sell');
        $query->with(['sells','sells.product']);
        $transactions = ($warehouse_id == 'all') ? $query : $query->where('warehouse_id', $warehouse_id );



        if($request->get('from') || $request->get('to')) {
            if(!is_null($from)){
                $from = Carbon::createFromFormat('Y-m-d',$from);
                $from =self::filterFrom($from);
                $transactions->whereBetween('date',[$from,$to]);
            }else{
                $transactions ->whereDate('date','<=',$to);

            }
        }


       // $transactions->with(['sells.product']);


        if(is_null($from)) {
            $AssociateArray = array(
                'data' => ''
            );
            return response()->json($AssociateArray ,200);
        }

        $AssociateArray = array(
            'data' => $transactions->get()
        );

        return response()->json($AssociateArray ,200);


    }


    /////////////////////STOCK REPRESENTATIVE REPORT//////////////////////

    public  function representStockReport(Request $request,$user){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        $userId = $user;

        //this for returning blank
        if ($userId=='null'){ return '';  }



        if(!is_null($from)) {
            $temp = $this->REPRESENT_temp_check($from, $to,$userId);
        }
        else{
            $temp = $this->REPRESENT_temp_check($nowDate, $nowDate,$userId);
        }


        $characteristics= Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw('sum(sells.quantity) as quantity,
                            sells.product_discount_percentage,                           
                            sum(sells.sub_total)as sub_total')
            ->whereBetween('date',[$from,$to])
            ->groupBy('sells.product_discount_percentage');


        $crossData= Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw('products.id,products.name,products.mrp,sum(sells.quantity) as quantity,
                            sells.product_discount_percentage,
                            sum(sells.product_discount_amount)as product_discount_amount,
                            sum(sells.sub_total)as sub_total')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','products.name','products.mrp',
                'sells.product_discount_percentage');

        $query = Transaction::where('transaction_type', 'sell');
        $query->with(['sells','sells.product']);

        if ($userId!='null'){
            $characteristics->where('user_id','=',$userId );
            $crossData->where('user_id','=',$userId );
        }


        $AssociateArray = array(
            'product' =>  $temp,
            'characteristics'=>$characteristics->get(),
            'crossData'=>$crossData->get()
        );

        return response()->json($AssociateArray ,200);
    }


    public function REPRESENT_temp_check($from,$to,$id )
    {
        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);


////////////////OPENING-PROCESS///////////////////////////

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');

            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->temporary();
        });




        $select1 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.id  , sum(representatives_stock.quantity)as Quantity,0')
             ->where('date','<',$from)
            ->where('representatives_stock.quantity','>',0)
            ->where('representatives_stock.user_id','=',$id)
            ->groupBy('products.id' );


        $select2 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.id   , sum(sells.quantity*-1)as Quantity,sum(sells.sub_total*-1)as Amount')
             ->where('date','<',$from)
            ->where('sells.user_id','=',$id)
            ->groupBy('products.id' );


        $select3 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id  , sum(damage_products.quantity*-1)as Quantity,sum(damage_products.unit_cost_price)as Amount')
             ->where('date','<',$from)
            ->where('damage_products.user_id','=',$id)
            ->groupBy('products.id');


        $select4 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id, sum(gift_products.quantity*-1)as Quantity,sum(gift_products.unit_cost_price)as Amount')
             ->whereDate('date','<',$from)
            ->where('gift_products.user_id','=',$id)
            ->groupBy('products.id');



        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select1);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select2);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select3);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select4);


//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('STOCK_ITEM_ID');
            $table->integer('TRAN_QUANTITY')->default(0);
            $table->integer('TRAN_AMOUNT')->default(0);
            $table->integer('OUTWARD_QUANTITY')->default(0);
            $table->integer('OUTWARD_AMOUNT')->default(0);
            $table->integer('INWARD_QUANTITY')->default(0);
            $table->integer('INWARD_AMOUNT')->default(0);
            $table->integer('GIFT_QUANTITY')->default(0);
            $table->integer('GIFT_COST')->default(0);
            $table->integer('DAMAGE_QUANTITY')->default(0);
            $table->integer('DAMAGE_COST')->default(0);
            $table->temporary();
        });


        $select0= Product::query()->select(array('id','name','mrp'));
        //DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','ITEM_MRP'], $select0);

        $select4= DB::table('TEMP_OPENING')
            ->selectRaw( 'STOCK_ITEM_ID,sum(TRAN_QUANTITY) as TRAN_QUANTITY, sum(TRAN_AMOUNT) as TRAN_AMOUNT')
            ->groupBy('STOCK_ITEM_ID');

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','TRAN_QUANTITY','TRAN_AMOUNT'], $select4);

        $select5 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,sum(sells.quantity*-1)as OUTWARD_QUANTITY,sum(sells.sub_total)as OUTWARD_AMOUNT')
            ->whereBetween('date',[$from,$to])
            ->where('sells.user_id','=',$id)
            ->groupBy('products.id');

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','OUTWARD_QUANTITY','OUTWARD_AMOUNT'], $select5);

            /*
            $select6 = Purchase::query()
                        ->join('products', 'purchases.product_id', '=', 'products.id')
                        ->selectRaw( 'products.id,products.name,0,0,0,0,sum(purchases.quantity)as INWARD_QUANTITY,sum(purchases.sub_total)as AMOUNT,0,0,0,0')
                        ->whereBetween('date',[$from,$to])
                        ->groupBy('products.id','products.name' );
            */

        $select6 = Representative::query()
            ->join('products', 'representatives_stock.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,sum(representatives_stock.quantity)  as INWARD_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->where('representatives_stock.quantity','>',0)
            ->where('representatives_stock.user_id','=',$id)
            ->groupBy('products.id');

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','INWARD_QUANTITY'], $select6);


        $select7 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,sum(gift_products.quantity*-1)as GIFT_QUANTITY, sum(gift_products.unit_cost_price*-1) as GIFT_COST')
            ->whereBetween('date',[$from,$to])
            ->where('gift_products.user_id','=',$id)
            ->groupBy('products.id');

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','GIFT_QUANTITY','GIFT_COST'], $select7);


        $select8 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,sum(damage_products.quantity*-1)as DAMAGE_QUANTITY, sum(damage_products.unit_cost_price*-1) as DAMAGE_COST')
            ->whereBetween('date',[$from,$to])
            ->where('damage_products.user_id','=',$id)
            ->groupBy('products.id');


        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','DAMAGE_QUANTITY','DAMAGE_COST'], $select8);

////////////////FINAL SELECTION//////////////////////////////
///
        $select0= Product::query()
            ->leftJoin('TEMP_TRANSACTION','products.id','=','TEMP_TRANSACTION.STOCK_ITEM_ID')
            ->selectRaw('products.name as STOCK_ITEM_NAME,   products.mrp as ITEM_MRP,
            STOCK_ITEM_ID, 
            COALESCE(SUM(TRAN_QUANTITY),0) as TRAN_QUANTITY,         
           COALESCE( sum(TRAN_AMOUNT),0) as  TRAN_AMOUNT,
           COALESCE(  sum(INWARD_QUANTITY),0) as INWARD_QUANTITY,
            COALESCE( sum(INWARD_AMOUNT),0) as  INWARD_AMOUNT,
           COALESCE(  sum(OUTWARD_QUANTITY),0) as OUTWARD_QUANTITY,
            COALESCE( sum(OUTWARD_AMOUNT),0) as  OUTWARD_AMOUNT,
           COALESCE(  sum(GIFT_QUANTITY),0) as GIFT_QUANTITY,
            COALESCE( sum(GIFT_COST * GIFT_QUANTITY ),0) as GIFT_COST,
            COALESCE( sum(DAMAGE_QUANTITY),0) as DAMAGE_QUANTITY,
            COALESCE( sum(DAMAGE_COST*DAMAGE_QUANTITY),0) as DAMAGE_COST')
            ->groupBy('STOCK_ITEM_ID', 'STOCK_ITEM_NAME', 'ITEM_MRP')
            ->orderBy('STOCK_ITEM_ID','ASC')
            ->get();



        Schema::drop('TEMP_OPENING');
        Schema::drop('TEMP_TRANSACTION');


        return $select0;
    }

/***********Represent Payment Summary*******************/
    public  function representPaymentReport(Request $request)
    {
        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));

        $from = $request->get('from');
        $to = $request->get('to');


         //Need to make a another query for the date wise report this only returns the summary


        if(!is_null($from)) {
        $temp = $this->REPRESENT_SUM_temp_check($from, $to);
        }
        else{
            $temp = $this->REPRESENT_SUM_temp_check($nowDate, $nowDate);
        }

        $AssociateArray = array(
            'payment' =>   $temp,
        );

        return response()->json($AssociateArray ,200);
    }


    public function REPRESENT_SUM_temp_check($from,$to )
    {
        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);


        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('USER_ID');
            $table->string('USER_NAME');
            $table->integer('TOTAL');
            $table->integer('EXPENSE');
            $table->integer('PAY');
            $table->temporary();
        });

        $select1 =Sell::selectRaw('users.id,users.name,sum(sells.sub_total) as total,0,0')
            ->leftJoin('users', 'users.id', '=', 'sells.user_id')
            ->whereBetween(DB::raw('DATE(date)'), array($from, $to))
            ->groupBy('users.name','users.id');


        $select2 =Payment::selectRaw('users.id,users.name,0,0,sum(payments.amount) as payment')
            ->leftJoin('users', 'users.id', '=', 'payments.user_id')
            ->whereBetween(DB::raw('DATE(date)'), array($from, $to))
            ->groupBy('users.name','users.id');

        $select3 =Expense::selectRaw('users.id,users.name,0,sum(expenses.amount) as expense,0')
            ->leftJoin('users', 'users.id', '=', 'expenses.user_id')
            ->whereBetween(DB::raw('DATE(date)'), array($from, $to))
            ->groupBy('users.name','users.id');

        DB::table('TEMP_OPENING')->insertUsing(['USER_ID','USER_NAME','TOTAL','EXPENSE','PAY'], $select1);
        DB::table('TEMP_OPENING')->insertUsing(['USER_ID','USER_NAME','TOTAL','EXPENSE','PAY'], $select2);
        DB::table('TEMP_OPENING')->insertUsing(['USER_ID','USER_NAME','TOTAL','EXPENSE','PAY'], $select3);

        $dataProduct = DB::table('TEMP_OPENING')
            ->selectRaw(
                'USER_ID, 
            USER_NAME,sum(TOTAL) as TOTAL,  sum(PAY) as  PAY, sum(EXPENSE) as  EXPENSE')
            ->groupBy('USER_ID','USER_NAME' )
            ->get();

        Schema::drop('TEMP_OPENING');


        return $dataProduct;
    }

    /***********Represent Payment Summary*******************/


    /***********Represent Sales Collection Summary*******************/
    public  function representSalesCollectionReport(Request $request)
    {
        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));



        $from = Carbon::createFromFormat('Y-m-d', $request->get('from'));
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$request->get('to'));
        $to= self::filterTo($to);


        $user=$request->get('userid');

        //Need to make a another query for the date wise report this only returns the summary
        $sells = Transaction::where('transaction_type', 'sell')->where('user_id','=',$user)->whereBetween('date',[$from,$to])->get();
        $expenses = Expense::whereBetween('date',[$from,$to])->where('user_id','=',$user)->get();
        $payment = Payment::whereBetween('date',[$from,$to])->where('user_id','=',$user)->get();

        //dd($request->get('userid'));


        $AssociateArray = array(
            'sells' =>   $sells,
            'expenses' => $expenses,
            'payments'=>$payment
        );

        return response()->json($AssociateArray ,200);
    }


    public function REPRESENT_COLL_temp_check($from,$to )
    {
        $from = Carbon::createFromFormat('Y-m-d',$from);
        $from = self::filterFrom($from);

        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to= self::filterTo($to);

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('USER_ID');
            $table->string('USER_NAME');
            $table->integer('TOTAL');
            $table->integer('EXPENSE');
            $table->integer('PAY');
            $table->temporary();
        });

        $select1 =Sell::selectRaw('users.id,users.name,sum(sells.sub_total) as total,0,0')
            ->leftJoin('users', 'users.id', '=', 'sells.user_id')
            ->whereBetween(DB::raw('DATE(date)'), array($from, $to))
            ->groupBy('users.name','users.id');


        $select2 =Payment::selectRaw('users.id,users.name,0,0,sum(payments.amount) as payment')
            ->leftJoin('users', 'users.id', '=', 'payments.user_id')
            ->whereBetween(DB::raw('DATE(date)'), array($from, $to))
            ->groupBy('users.name','users.id');

        $select3 =Expense::selectRaw('users.id,users.name,0,sum(expenses.amount) as expense,0')
            ->leftJoin('users', 'users.id', '=', 'expenses.user_id')
            ->whereBetween(DB::raw('DATE(date)'), array($from, $to))
            ->groupBy('users.name','users.id');

        DB::table('TEMP_OPENING')->insertUsing(['USER_ID','USER_NAME','TOTAL','EXPENSE','PAY'], $select1);
        DB::table('TEMP_OPENING')->insertUsing(['USER_ID','USER_NAME','TOTAL','EXPENSE','PAY'], $select2);
        DB::table('TEMP_OPENING')->insertUsing(['USER_ID','USER_NAME','TOTAL','EXPENSE','PAY'], $select3);

        $dataProduct = DB::table('TEMP_OPENING')
            ->selectRaw(
                'USER_ID, 
            USER_NAME,sum(TOTAL) as TOTAL,  sum(PAY) as  PAY, sum(EXPENSE) as  EXPENSE')
            ->groupBy('USER_ID','USER_NAME' )
            ->get();

        Schema::drop('TEMP_OPENING');

        return $dataProduct;
    }

    /***********Represent Sales Collection  Summary*******************/




    public function postProfitReport (Request $request){

        $branch_id = 'all';
        $branch_name = ($branch_id == 'all') ? 'All Branch' : Warehouse::where('id', $branch_id)->first()->name;

        $query = Transaction::where('transaction_type', 'sell');
        $transactions = ($branch_id == 'all') ? $query : $query->where('warehouse_id', $branch_id );

        $from = Carbon::parse($request->get('from')?:date('Y-m-d'))->startOfDay();
        $to = Carbon::parse($request->get('to')?:date('Y-m-d'))->endOfDay();

        $transactions = $transactions->whereBetween('date',[$from,$to]);

        $total_selling_price = $transactions->get()->sum('total');
        $total_cost_price = $transactions->get()->sum('total_cost_price');
        $gross_profit = $total_selling_price - $total_cost_price;

        $expenses = Expense::whereBetween('created_at',[$from,$to])->get();
        $cloneExpense = clone $expenses;
        $total_expense = $cloneExpense->sum('amount');

        $net_profit = $gross_profit - $total_expense;

        $total_tax = $transactions->get()->sum('total_tax');

        $net_profit_after_tax = $net_profit - $total_tax;




            $AssociateArray = array(
                'total_selling_price' =>  $total_selling_price,
                'total_cost_price'=>$total_cost_price,
                'gross_profit'=>$gross_profit,
                'total_expense'=>$total_expense,
                'expenses'=>$expenses,
                'net_profit'=>$net_profit,
                'total_tax'=>$total_tax,
                'net_profit_after_tax'=>$net_profit_after_tax,

            );



        return response()->json($AssociateArray ,200);


    }



}
