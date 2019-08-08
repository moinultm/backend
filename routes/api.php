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

    Route::resource('roles', 'RoleController');
    Route::resource('users', 'UsersController');
    Route::resource('profiles', 'ProfileController');
});
