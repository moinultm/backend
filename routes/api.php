<?php

Route::group([
    'middleware' => 'api',
], function ($router) {

    //All Unprotected Roles
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('signup', 'AuthController@signup');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
    Route::post('sendPasswordResetLink', 'ResetPasswordController@sendEmail');
    Route::post('resetPassword', 'ChangePasswordController@process');

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

    //sell
    Route::resource('sell', 'SellController');
    Route::get('sell/{transaction}/details', 'SellController@details');

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
    Route::resource('settings', 'SettingsController');
    Route::post('settings/{settings}', 'SettingsController@update');


    //representetive
    Route::resource('represent', 'RepresentativeController');
    Route::get('users/{user}/representative', 'RepresentativeController@getUser');
    Route::get('represent/{user}/sells', 'RepresentativeController@getSells');
    Route::get('represent/{user}/invoices', 'RepresentativeController@getInvoices');
    Route::get('represent/{user}/details', 'RepresentativeController@getDetails');


    //gifts
    Route::resource('gifts', 'GiftProductController');

    Route::resource('damages', 'DamageProductController');

    //Dashboard
    Route::get('dashboard', 'DashboardController@index');


    //Reporting
    Route::get('report/product-summary', 'ReportingController@productSummary');
    Route::get('report/{user}/represent-stock', 'ReportingController@representSummary');

    Route::get('report/product-sells-report', 'ReportingController@productSellReport');

    Route::get('report/stock-general-report', 'ReportingController@stockGeneralReport');


    Route::get('report/purchase-report', 'ReportingController@postPurchaseReport');
    Route::get('report/sells-report', 'ReportingController@postSellsReport');


});
