<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('products')
            ->insert([[
                'name' => 'G-Acne Gel',
                'code' => '100001',
                'category_id'=>'1',
                'subcategory_id'=>'1',
                'quantity'=>'0',
                'details'=>'Not Available',
                'cost_price'=>'0',
                'mrp'=>'0',
                'tax_id'=>'0',
                'minimum_retail_price'=>'0',
                'unit'=>'Pcs',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
                [
                    'name' => 'Melagm-EK Cream',
                    'code' => '100002',
                    'category_id'=>'1',
                    'subcategory_id'=>'1',
                    'quantity'=>'0',
                    'details'=>'Not Available',
                    'cost_price'=>'0',
                    'mrp'=>'0',
                    'tax_id'=>'0',
                    'minimum_retail_price'=>'0',
                    'unit'=>'Pcs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'name' => ' GM-60 Facewash',
                    'code' => '100003',
                    'category_id'=>'1',
                    'subcategory_id'=>'1',
                    'quantity'=>'0',
                    'details'=>'Not Available',
                    'cost_price'=>'0',
                    'mrp'=>'0',
                    'tax_id'=>'0',
                    'minimum_retail_price'=>'0',
                    'unit'=>'Pcs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'name' => ' GM-KIT Bar',
                    'code' => '100004',
                    'category_id'=>'1',
                    'subcategory_id'=>'1',
                    'quantity'=>'0',
                    'details'=>'Not Available',
                    'cost_price'=>'0',
                    'mrp'=>'0',
                    'tax_id'=>'0',
                    'minimum_retail_price'=>'0',
                    'unit'=>'Pcs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'name' => 'GT-KIT Shampoo',
                    'code' => '100005',
                    'category_id'=>'1',
                    'subcategory_id'=>'1',
                    'quantity'=>'0',
                    'details'=>'Not Available',
                    'cost_price'=>'0',
                    'mrp'=>'0',
                    'tax_id'=>'0',
                    'minimum_retail_price'=>'0',
                    'unit'=>'Pcs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'name' => 'O’Vera Lotion',
                    'code' => '100006',
                    'category_id'=>'1',
                    'subcategory_id'=>'1',
                    'quantity'=>'0',
                    'details'=>'Not Available',
                    'cost_price'=>'0',
                    'mrp'=>'0',
                    'tax_id'=>'0',
                    'minimum_retail_price'=>'0',
                    'unit'=>'Pcs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],

                [
                    'name' => 'Sealer Cream',
                    'code' => '100007',
                    'category_id'=>'1',
                    'subcategory_id'=>'1',
                    'quantity'=>'0',
                    'details'=>'Not Available',
                    'cost_price'=>'0',
                    'mrp'=>'0',
                    'tax_id'=>'0',
                    'minimum_retail_price'=>'0',
                    'unit'=>'Pcs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'name' => 'Ultrasun-SLX Aqua Lotion',
                    'code' => '100008',
                    'category_id'=>'1',
                    'subcategory_id'=>'1',
                    'quantity'=>'0',
                    'details'=>'Not Available',
                    'cost_price'=>'0',
                    'mrp'=>'0',
                    'tax_id'=>'0',
                    'minimum_retail_price'=>'0',
                    'unit'=>'Pcs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                             [
                'name' => 'Foltfix',
                    'code' => '100009',
                    'category_id'=>'1',
                    'subcategory_id'=>'1',
                    'quantity'=>'0',
                    'details'=>'Not Available',
                    'cost_price'=>'0',
                    'mrp'=>'0',
                    'tax_id'=>'0',
                    'minimum_retail_price'=>'0',
                    'unit'=>'Pcs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'name' => 'GM-TAR',
                    'code' => '100010',
                    'category_id'=>'1',
                    'subcategory_id'=>'1',
                    'quantity'=>'0',
                    'details'=>'Not Available',
                    'cost_price'=>'0',
                    'mrp'=>'0',
                    'tax_id'=>'0',
                    'minimum_retail_price'=>'0',
                    'unit'=>'Pcs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]
]
            );
    }
}
