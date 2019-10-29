<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('subcategories')
            ->insert([            
                 'category_id'=>'1',
                'subcategory_name' => 'Gm',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
    }
}
