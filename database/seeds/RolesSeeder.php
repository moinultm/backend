<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('roles')
            ->insert([
                [
                    'code' => 'ROLE_OWNER_PRIVILEGE',
                    'designation' => 'OWNER_PRIVILEGE',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_MANAGER_PRIVILEGE',
                    'designation' => 'MANAGER_PRIVILEGE',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_ADMIN_PRIVILEGE',
                    'designation' => 'ADMIN_PRIVILEGE',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_DASHBOARD_ACCESS',
                    'designation' => 'Dashboard Access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ] ,
                [
                'code' => 'ROLE_ACL_ACCESS',
                'designation' => 'ACL_ACCESS',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ], [
                    'code' => 'ROLE_ACL_MANAGE',
                    'designation' => 'ACL Management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_SETTINGS_ACCESS',
                    'designation' => 'Settings access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_SETTINGS_MANAGE',
                    'designation' => 'Settings management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ] ,

                [
                    'code' => 'ROLE_SALES_ACCESS',
                    'designation' => 'Sales access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_SALES_MANAGE',
                    'designation' => 'Sales management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],

                [
                    'code' => 'ROLE_ORDER_ACCESS',
                    'designation' => 'Order access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_ORDER_MANAGE',
                    'designation' => 'Order management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],

                [
                    'code' => 'ROLE_PURCHASE_ACCESS',
                    'designation' => 'Purchase access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_PURCHASE_MANAGE',
                    'designation' => 'Purchase management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_PRODUCT_ACCESS',
                    'designation' => 'Product access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_PRODUCT_MANAGE',
                    'designation' => 'Product management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_CATEGORY_ACCESS',
                    'designation' => 'Category access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_CATEGORY_MANAGE',
                    'designation' => 'Category management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],

                [
                    'code' => 'ROLE_REPRESENT_ACCESS',
                    'designation' => 'Representative  access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_REPRESENT_MANAGE',
                    'designation' => 'Representative management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_CLIENT_ACCESS',
                    'designation' => 'Client access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_CLIENT_MANAGE',
                    'designation' => 'Client management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_ATTENDANCE_ACCESS',
                    'designation' => 'Attendance management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_ATTENDANCE_MANAGE',
                    'designation' => 'Attendance management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],

                [
                    'code' => 'ROLE_EXPENSE_ACCESS',
                    'designation' => 'Expense access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_EXPENSE_MANAGE',
                    'designation' => 'Expense management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],

                [
                    'code' => 'ROLE_REPORT_ACCESS',
                    'designation' => 'Report access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_REPORT_MANAGE',
                    'designation' => 'Report management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],

                [
                    'code' => 'ROLE_HRM_ACCESS',
                    'designation' => 'HR  access',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                [
                    'code' => 'ROLE_HRM_MANAGE',
                    'designation' => 'HR management',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],


            ]);
    }
}
