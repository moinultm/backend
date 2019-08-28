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

    //supplier
    Route::resource('supplier', 'SupplierController');


    //Product
    Route::resource('product', 'ProductController');
    Route::get('parent', 'SubcategoryController@parentReq');
    Route::get('product/{product}/details', 'ProductController@productDetails');
    Route::post('product/{product}', 'ProductController@update');
   //sell
    Route::resource('sell', 'SellController');

});
