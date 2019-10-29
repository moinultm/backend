<?php

namespace App\Http\Controllers;

use App\Expense;
use App\Payment;
use App\Product;
use App\Purchase;
use App\Sell;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $settings='tile-box';

        /*Dashboard box with last seven days graph*/
        $dayNames = [];
        $lastSevenDaySells = [];
        $lastSevenDayPurchases = [];
        $todays_stats['total_selling_quantity'] = 0;
        $todays_stats['total_purchasing_quantity'] = 0;

        for($i = 0; $i <= 5; $i++)
        {
            $dayNames[] = now()->subDays($i)->format('D');

            //check if today or not
            if($i == 0)
            {
                $getNow = now()->format("Y-m-d");
                $getStarts = Carbon::createFromFormat('Y-m-d', $getNow)->startOfDay();
                $getEnds = Carbon::createFromFormat('Y-m-d', $getNow)->endOfDay();
                //calculation of total expense today
                /*$todaysExpense = Expense::whereBetween('created_at',[$getStarts,$getEnds])->sum('amount');*/

                if( $settings == 'tile-box'){
                    //calculation of total sells & invoices today
                    $todaysInvoices = Transaction::whereBetween('date',[$getStarts,$getEnds])->where('transaction_type', 'sell')->get();

                    foreach($todaysInvoices as $todaysInvoice){
                        $todays_stats['total_selling_quantity'] = $todays_stats['total_selling_quantity'] + $todaysInvoice->sells->sum('quantity');
                    }

                    //calculation of total purchasing price and purchased quantity today
                    $todaysBills = Transaction::whereBetween('date',[$getStarts,$getEnds])->where('transaction_type', 'purchase')->get();

                    foreach($todaysBills as $todaysBill){
                        $todays_stats['total_purchasing_quantity'] = $todays_stats['total_purchasing_quantity'] + $todaysBill->purchases->sum('quantity');
                    }
                }
            }else
            {
                $getNow = now()->subDays($i)->format('Y-m-d');
                $getStarts = Carbon::createFromFormat('Y-m-d', $getNow)->startOfDay();
                $getEnds = Carbon::createFromFormat('Y-m-d', $getNow)->endOfDay();
            }

            $lastSevenDaySells[] = Transaction::whereBetween('date' , [$getStarts , $getEnds])->where('transaction_type', 'sell')->sum('net_total');
            $lastSevenDayPurchases[] = Transaction::whereBetween('date' , [$getStarts , $getEnds])->where('transaction_type', 'purchase')->sum('net_total');
            $lastSevenDayTransactions[] = Payment::whereBetween('date',[$getStarts,$getEnds])->sum('amount');

        }

        //today's total transaction
        $todays_stats['total_transactions_today'] = $lastSevenDayTransactions[0];
        //today's total selling price
        $todays_stats['total_selling_price'] = $lastSevenDaySells[0];
        //today's total purchasing price
        $todays_stats['total_purchasing_price'] = $lastSevenDayPurchases[0];
        //get the name of last seven days
        $daynames = array_reverse($dayNames);

        $lastSevenDaySells = implode(',', array_reverse($lastSevenDaySells));
        $lastSevenDayPurchases = implode(',', array_reverse($lastSevenDayPurchases));
        $lastSevenDayTransactions = implode(',', array_reverse($lastSevenDayTransactions));
        /*Dashboard box with last seven days graph ends*/

        //top 5 products
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $stock_value_by_cost = 0;
        $stock_value_by_price = 0;
        $products = Product::select('id','name','cost_price', 'mrp', 'quantity')->get();
        foreach($products as $product){
            $top_products_all[$product->name] =  $product->sells->sum('quantity');
            $top_products_month[$product->name] =  $product->sells()->whereBetween('date',[$start,$end])->sum('quantity');

            //cost & sell value of stock
            $stock_value_by_cost = $stock_value_by_cost + $product->quantity * $product->cost_price;

            $stock_value_by_price = $stock_value_by_price + $product->quantity * $product->mrp;
        }

        if($products->count() != 0){
            arsort($top_products_month);
            $top_products =  array_slice($top_products_month, 0, 5);
        }else{
            $top_products = [];
        }

        $top_product_name = [];
        $selling_quantity = [];
        foreach($top_products as $x => $top_product){
            $top_product_name[] = $x;
            $selling_quantity[] = $top_product;
        }
        /*top products ends*/

        //stock value by cost price and mrp
        $profit_estimate = $stock_value_by_price - $stock_value_by_cost;
        $stock = [$stock_value_by_cost, $stock_value_by_price, $profit_estimate];

        //sell vs purchase graph
        for($i = 0; $i <= 5; $i++){
            $nowM = Carbon::now()->month;
            $nowY = Carbon::now()->year;
            $now = $nowY."-".$nowM."-15 00:00:00";
            $now = Carbon::parse($now);

            if($i == 0){
                $now = $now->format("Y-m-d");
            }else{
                $now = $now->subMonths($i)->format('Y-m-d');
            }

            $from = Carbon::createFromFormat('Y-m-d', $now )->startOfMonth();
            $to = Carbon::createFromFormat('Y-m-d', $now )->endOfMonth();
            $month = Carbon::createFromFormat('Y-m-d',$now)->format("M");
            $months[] = $month;

            $transactionThisMonth = Transaction::whereBetween('date' , [$from , $to]);
            $sellDiscount = $transactionThisMonth->where('transaction_type', 'sell')->sum('discount');
            $total_selling_tax = Transaction::whereBetween('date' , [$from , $to])->where('transaction_type', 'sell')->sum('total_tax');

            $purchaseDiscount = Transaction::whereBetween('date' , [$from , $to])->where('transaction_type', 'purchase')->sum('discount');
            $total_purchasing_tax = Transaction::whereBetween('date' , [$from , $to])->where('transaction_type', 'purchase')->sum('total_tax');

            $sell_array = Sell::whereBetween('date' , [$from , $to]);
            $sells[] = $sell_array->sum('sub_total') + $total_selling_tax - $sellDiscount;

            $purchase_array = Purchase::whereBetween('date' , [$from , $to]);
            $purchases[] = $purchase_array->sum('sub_total') + $total_purchasing_tax - $purchaseDiscount;


            //last six month's profit calculation
            $lastSixMonthsSellTransactions = Transaction::whereBetween('date' , [$from , $to])->where('transaction_type', 'sell');

            $last_six_months_profit[] = $lastSixMonthsSellTransactions->sum('total') - $lastSixMonthsSellTransactions->sum('total_cost_price');
        }

        //this populates date as per day by database datetime

        $from = Carbon::parse(date('Y-m-d'))->startOfDay();
        $to = Carbon::parse(date('Y-m-d'))->endOfDay();


        $todaySellList= Transaction::where('transaction_type', 'sell')
            ->whereDate('date','>', Carbon::now()->subDays(30))
            ->orderBy('date', 'desc')->get();

        $todayPurchaseList= Transaction::where('transaction_type', 'purchase')
            ->whereDate('date', '>', Carbon::now()->subDays(30))
            ->orderBy('date', 'desc')->get();

        $expenses = Expense::whereBetween('created_at',[$from,$to])->get();
        $cloneExpense = clone $expenses;
        $todayExpenses = $cloneExpense->sum('amount');

        $AssociateArray = array(
            'todays_stats'=>$todays_stats,
            'today_sell_list'=>$todaySellList,
            'todayPurchaseList'=>$todayPurchaseList,
            'top_product_name'=>$top_product_name,
            'selling_quantity'=>$selling_quantity,
            'stock'=>$stock,
            'months'=>$months,
            'sells'=>$sells,
            'purchases'=>$purchases,
            'last_six_months_profit'=>$last_six_months_profit,
            'lastSevenDaySells'=>$lastSevenDaySells,
            'lastSevenDayPurchases'=>$lastSevenDayPurchases,
            'lastSevenDayTransactions'=>$lastSevenDayTransactions,
            'daynames'=>$daynames,
            'today_expenses'=>$todayExpenses
        );

        return response()->json( $AssociateArray, 200);


    }
}
