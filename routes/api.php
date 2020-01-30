<?php

//php artisan passport:keys
//php artisan passport:client --personal
//php artisan passport:client --password


Route::group([
    'middleware' => 'api',
], function ($router) {
    Route::get('settings', 'SettingsController@getIndex');

});


Route::group([
    'middleware' => 'auth:api',
], function ($router) {

    /*All Unprotected Roles
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('signup', 'AuthController@signup');
    Route::post('refresh', 'AuthController@refresh');

    Route::post('me', 'AuthController@me');
    Route::post('sendPasswordResetLink', 'ResetPasswordController@sendEmail');
    Route::post('resetPassword', 'ChangePasswordController@process');
*/
    // ATTENDANCE



    Route::get('attendance/get', 'AttendanceController@isAttendance');
    Route::get('attendance/in', 'AttendanceController@postClockIn');
    Route::get('attendance/out', 'AttendanceController@postClockOut');

    Route::post('attendance/{attendance}', 'AttendanceController@update');


    Route::resource('attendance', 'AttendanceController');

    //security
    Route::resource('roles', 'RoleController');
    Route::resource('profiles', 'ProfileController');
    Route::resource('users', 'UsersController');
    Route::post('users/{user}', 'UsersController@update');


    //category
    Route::resource('category', 'CategoryController');

    //subcategory
    Route::resource('subcategory', 'SubcategoryController');
    Route::get('subcategory/{subcategory}/product', 'SubcategoryController@productList');

    //customer
    Route::resource('customer', 'CustomerController');
    Route::get('customer/{client}/details', 'CustomerController@details');
    Route::get('customer/{client}/report', 'CustomerController@saleDetails');



    //supplier
    Route::resource('supplier', 'SupplierController');
    Route::get('supplier/{client}/details', 'SupplierController@details');
    Route::get('supplier/{client}/summary-trans', 'SupplierController@tranSummary');

    //Payment
    Route::resource('payment', 'PaymentController');

    //Product
    Route::resource('product', 'ProductController');
    Route::get('parent', 'SubcategoryController@parentReq');
    Route::get('product/{product}/details', 'ProductController@productDetails');
    Route::post('product/{product}', 'ProductController@update');


    Route::get('product/{product}/update-price', 'ProductController@updatePrice');


    //sell
    Route::resource('sell', 'SellController');
    Route::get('sell/{transaction}/details', 'SellController@details');
    Route::delete('sell/{transaction}/delete/', 'SellController@deleteSell');

    //sells order
    Route::resource('sells-order', 'SellsOrderController');
    Route::get('sell/{transaction}/order-details', 'SellsOrderController@details');
    Route::get('sell/{transaction}/order-edit', 'SellsOrderController@getAlter');

    Route::post('sell/{order}/order-post', 'SellController@orderToInvoice');



    //return sells
    Route::get('sell/return/{transaction}', 'SellController@returnSell');
    Route::post('sell/return/{transaction}', 'SellController@returnSellPost');

    //purchase
    Route::resource('purchase', 'PurchaseController');
    Route::get('purchase/{transaction}/details', 'PurchaseController@details');


    Route::get('purchase/list/pi', 'PurchaseController@getLists');
    Route::get('sell/list/si', 'SellController@getLists');


//warehouse
    Route::resource('warehouse', 'WarehouseController');
//Expenses
    Route::resource('expense', 'ExpenseController');

//vatTax
    Route::resource('vat', 'TaxController');

//settings
    //Route::resource('settings', 'SettingsController');
    Route::post('settings/{settings}', 'SettingsController@update');
    //representetive
    Route::resource('represent', 'RepresentativeController');
    Route::get('users/{user}/representative', 'RepresentativeController@getUser');
           Route::get('represent/{user}/challans', 'RepresentativeController@getChallans');

    Route::get('represent/{user}/sells', 'RepresentativeController@getSells');
    Route::get('represent/{user}/invoices', 'RepresentativeController@getInvoices');

    Route::get('represent/{id}/details', 'RepresentativeController@getDetails');

    Route::get('represent/{id}/receiving', 'RepresentativeController@getConformed');

    //gifts
    Route::resource('gifts', 'GiftProductController');

    Route::resource('damages', 'DamageProductController');

    //Dashboard
    Route::get('dashboard', 'DashboardController@index');

    //Reporting
    Route::get('report/product-report', 'ReportingController@productReport');
    Route::get('report/{user}/represent-stock', 'ReportingController@representSummary');

    Route::get('report/{user}/represent-stock-report', 'ReportingController@representStockReport');


    Route::get('report/represent-payment-report', 'ReportingController@representPaymentReport');

    Route::get('report/represent-collection-report', 'ReportingController@representSalesCollectionReport');


    Route::get('report/product-sells-report', 'ReportingController@productSellReport');
    Route::get('report/stock-general-report', 'ReportingController@stockGeneralReport');

    //27-01-2020
    Route::get('report/stock-report', 'ReportingController@stockReport');

    Route::get('report/{user}/challan-report', 'ReportingController@challanReport');

    Route::get('report/stockin-report', 'ReportingController@stockInReport');
    Route::get('report/stockout-report', 'ReportingController@stockOutReport');

    Route::get('report/{user}/damage-report', 'ReportingController@damageReport');
    Route::get('report/{user}/gift-report', 'ReportingController@giftReport');

    Route::get('report/purchase-report', 'ReportingController@postPurchaseReport');
    Route::get('report/sells-report', 'ReportingController@postSellsReport');
    Route::get('report/profit-loss-report', 'ReportingController@postProfitReport');

});


//Delete Sales
Route::post('sell/{transaction}/delete', 'SellController@deleteSell')->middleware(['auth:api', 'roles:ROLE_SALES_MANAGE']);

//Delete Purchase
Route::post('purchase/{transaction}/delete', 'PurchaseController@deletePurchase')->middleware(['auth:api', 'roles:ROLE_PURCHASE_MANAGE']);

//Delete Challan
Route::post('represent/{transaction}/delete', 'RepresentativeController@deleteChallan')->middleware(['auth:api', 'roles:ROLE_SALES_MANAGE']);

//Delete Gifts
Route::post('gifts/{transaction}/delete', 'GiftProductController@deleteGift')->middleware(['auth:api', 'roles:ROLE_PRODUCT_MANAGE']);

//Delete Damage
Route::post('damages/{transaction}/delete', 'DamageProductController@deleteDamage')->middleware(['auth:api', 'roles:ROLE_PRODUCT_MANAGE']);

//Delete Order
Route::post('sells-order/{transaction}/delete', 'SellsOrderController@deleteOrder')->middleware(['auth:api', 'roles:ROLE_SALES_MANAGE']);