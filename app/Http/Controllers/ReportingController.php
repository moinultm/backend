<?php

namespace App\Http\Controllers;

use App\DamageProduct;
use App\GiftProduct;
use App\Product;
use App\Purchase;
use App\Representative;
use App\Sell;
use App\Traits\Paginator;
use App\Transaction;
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

//need to fix dates query

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));


        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');



/*        $product= Sell::query()
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
     */




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


////////////////OPENING-PROCESS///////////////////////////

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY');
            $table->integer('TRAN_AMOUNT');
            $table->temporary();
        });


        $select1= Product::query()->select(array('name','opening_stock','opening_stock_value'));

        $select2 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , sum(sells.quantity*-1)as Quantity,sum(sells.sub_total*-1)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.name' );

        $select3 = Purchase::query()
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , sum(purchases.quantity)as Quantity,sum(purchases.sub_total)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.name' );

        $select4 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , sum(damage_products.quantity)as Quantity,sum(damage_products.unit_cost_price)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.name' );

        $select5 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , sum(gift_products.quantity)as Quantity,sum(gift_products.unit_cost_price)as Amount')
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
            $table->integer('TRAN_QUANTITY');
            $table->integer('TRAN_AMOUNT');
            $table->integer('OUTWARD_QUANTITY');
            $table->integer('OUTWARD_AMOUNT');
            $table->integer('INWARD_QUANTITY');
            $table->integer('INWARD_AMOUNT');
            $table->integer('GIFT_QUANTITY');
            $table->integer('DAMAGE_QUANTITY');
            $table->temporary();
        });


        $select4= DB::table('TEMP_OPENING')
            ->selectRaw('STOCK_ITEM_NAME , sum(TRAN_QUANTITY) as TRAN_QUANTITY , sum(TRAN_AMOUNT) as TRAN_AMOUNT,0,0,0,0,0,0')
            ->groupBy('STOCK_ITEM_NAME' );


        $select5 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,sum(sells.quantity*-1)as OUTWARD_QUANTITY,sum(sells.sub_total*-1)as AMOUNT,0,0,0,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );

        $select6 = Purchase::query()
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,sum(purchases.quantity)as INWARD_QUANTITY,sum(purchases.sub_total)as AMOUNT,0,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );


        $select7 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,0,0,sum(gift_products.quantity)as GIFT_QUANTITY,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.name' );


        $select8 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.name,0,0,0,0,0,0,0,sum(damage_products.quantity)as GIFT_QUANTITY')
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
              sum(DAMAGE_QUANTITY) as DAMAGE_QUANTITY'
                )
            ->groupBy('STOCK_ITEM_NAME' )
            ->get();


        Schema::drop('TEMP_OPENING');
        Schema::drop('TEMP_TRANSACTION');



        return $dataProduct;
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
            ->where('date','<=',$from)
            ->where('user_id','=',$id)
            ->groupBy('products.name' );

        //AS OUT WARD
        $select3= Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.name  , sum(sells.quantity*-1)as Quantity,sum(sells.sub_total*-1)as Amount')
            ->where('date','<=',$from)
            ->where('user_id','=',$id)
            ->groupBy('products.name' );



        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME'], $select1);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY'], $select2);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select3);

//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY');
            $table->integer('TRAN_AMOUNT');
            $table->integer('OUTWARD_QUANTITY');
            $table->integer('OUTWARD_AMOUNT');
            $table->integer('INWARD_QUANTITY');
            $table->integer('INWARD_AMOUNT');
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
                $sells->where('date','<=',$to);
                $purchases->where('date','<=',$to);
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



    public  function sellsStatusReport(Request $request){

        $warehouse_id = 'all';
        $warehouse_name = ($warehouse_id == 'all') ? 'All Branch' : Warehouse::where('id', $warehouse_id);

        $query = Transaction::where('transaction_type', 'sell');
        $query->with(['sells','sells.product']);
        $transactions = ($warehouse_id == 'all') ? $query : $query->where('warehouse_id', $warehouse_id );

        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');
        $to = Carbon::createFromFormat('Y-m-d',$to);
        $to = self::filterTo($to);

        if($request->get('from') || $request->get('to')) {
            if(!is_null($from)){
                $from = Carbon::createFromFormat('Y-m-d',$from);
                $from =self::filterFrom($from);
                $transactions->whereBetween('date',[$from,$to]);
            }else{
                $transactions->where('date','<=',$to);

            }
        }



        $AssociateArray = array(
            'data' => $transactions->get()
        );
        return response()->json($AssociateArray ,200);


    }



    ////////////Stock General Report///////////////
    public  function stockGeneralReport(Request $request){

        $date=Carbon::now();
        $nowDate = date('Y-m-d', strtotime($date));
        $from = $request->get('from');
        $to = $request->get('to')?:date('Y-m-d');



        if(!is_null($from)) {
            $temp = $this->summary_temp_check($from, $to);
        }
        else{
            $temp = $this->summary_temp_check($nowDate, $nowDate);
        }


        $characteristics= Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw('products.id,products.name,products.mrp,sum(sells.quantity) as quantity,
                            sells.product_discount_percentage,
                            sum(sells.product_discount_amount)as product_discount_amount,
                            sum(sells.sub_total)as sub_total')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','products.name','products.mrp',
                'sells.product_discount_percentage'
            );

        $crossData= Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw('products.id,products.name,products.mrp,sum(sells.quantity) as quantity,
                            sells.product_discount_percentage,
                            sum(sells.product_discount_amount)as product_discount_amount,
                            sum(sells.sub_total)as sub_total')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','products.name','products.mrp',
                'sells.product_discount_percentage'
            );


        $AssociateArray = array(
            'data' =>  $temp,
            'characteristics'=>$characteristics->get(),
            'crossData'=>$crossData->get()
        );



        return response()->json($AssociateArray ,200);
    }


    public function summary_temp_check($from,$to )
    {


////////////////OPENING-PROCESS///////////////////////////

        Schema::create('TEMP_OPENING', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_ID');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY');
            $table->integer('TRAN_AMOUNT');
            $table->temporary();
        });


        $select1= Product::query()->select(array('id','name','opening_stock','opening_stock_value'));

        $select2 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.id ,products.name  , sum(sells.quantity*-1)as Quantity,sum(sells.sub_total*-1)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.id','products.name' );

        $select3 = Purchase::query()
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->selectRaw( 'products.id, products.name  , sum(purchases.quantity)as Quantity,sum(purchases.sub_total)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.id','products.name' );


        $select4 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id, products.name  , sum(damage_products.quantity)as Quantity,sum(damage_products.unit_cost_price)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.id','products.name' );


        $select5 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id, products.name  , sum(gift_products.quantity)as Quantity,sum(gift_products.unit_cost_price)as Amount')
            ->where('date','<=',$from)
            ->groupBy('products.id','products.name' );




        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select1);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select2);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select3);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select4);
        DB::table('TEMP_OPENING')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT'], $select5);

//////////////////TRANSACTION-PROCESS//////////////////////////////

        Schema::create('TEMP_TRANSACTION', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STOCK_ITEM_ID');
            $table->string('STOCK_ITEM_NAME');
            $table->integer('TRAN_QUANTITY');
            $table->integer('TRAN_AMOUNT');
            $table->integer('OUTWARD_QUANTITY');
            $table->integer('OUTWARD_AMOUNT');
            $table->integer('INWARD_QUANTITY');
            $table->integer('INWARD_AMOUNT');
            $table->integer('GIFT_QUANTITY');
            $table->integer('DAMAGE_QUANTITY');
            $table->temporary();
        });


        $select4= DB::table('TEMP_OPENING')
            ->selectRaw( 'STOCK_ITEM_ID,STOCK_ITEM_NAME , sum(TRAN_QUANTITY) as TRAN_QUANTITY , sum(TRAN_AMOUNT) as TRAN_AMOUNT,0,0,0,0,0,0')
            ->groupBy('STOCK_ITEM_ID','STOCK_ITEM_NAME' );


        $select5 = Sell::query()
            ->join('products', 'sells.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,products.name,0,0,sum(sells.quantity*-1)as OUTWARD_QUANTITY,sum(sells.sub_total*-1)as AMOUNT,0,0,0,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','products.name' );


        $select6 = Purchase::query()
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,products.name,0,0,0,0,sum(purchases.quantity)as INWARD_QUANTITY,sum(purchases.sub_total)as AMOUNT,0,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','products.name' );



        $select7 = GiftProduct::query()
            ->join('products', 'gift_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,products.name,0,0,0,0,0,0,sum(gift_products.quantity)as GIFT_QUANTITY,0')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','products.name' );



        $select8 = DamageProduct::query()
            ->join('products', 'damage_products.product_id', '=', 'products.id')
            ->selectRaw( 'products.id,products.name,0,0,0,0,0,0,0,sum(damage_products.quantity)as GIFT_QUANTITY')
            ->whereBetween('date',[$from,$to])
            ->groupBy('products.id','products.name' );




        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select4);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select5);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select6);

        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select7);
        DB::table('TEMP_TRANSACTION')->insertUsing(['STOCK_ITEM_ID','STOCK_ITEM_NAME','TRAN_QUANTITY','TRAN_AMOUNT','OUTWARD_QUANTITY','OUTWARD_AMOUNT','INWARD_QUANTITY','INWARD_AMOUNT','GIFT_QUANTITY','DAMAGE_QUANTITY'], $select8);


////////////////FINAL SELECTION//////////////////////////////

        $dataProduct = DB::table('TEMP_TRANSACTION')

            ->selectRaw('STOCK_ITEM_NAME , 
            STOCK_ITEM_ID,
            sum(TRAN_QUANTITY) as TRAN_QUANTITY , 
            sum(TRAN_AMOUNT) as  TRAN_AMOUNT,
            sum(INWARD_QUANTITY) as INWARD_QUANTITY,
            sum(INWARD_AMOUNT) as  INWARD_AMOUNT,
            sum(OUTWARD_QUANTITY) as OUTWARD_QUANTITY,
              sum(OUTWARD_AMOUNT) as  OUTWARD_AMOUNT,
              sum(GIFT_QUANTITY) as GIFT_QUANTITY,
              sum(DAMAGE_QUANTITY) as DAMAGE_QUANTITY'
            )
            ->groupBy('STOCK_ITEM_ID','STOCK_ITEM_NAME' )
            ->get();


        Schema::drop('TEMP_OPENING');
        Schema::drop('TEMP_TRANSACTION');


        return $dataProduct;
    }
}
