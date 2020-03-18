<?php

Route::group(['namespace' => 'Abs\CustomerPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'customer-pkg'], function () {

	//CUSTOMERS
	Route::get('/customers/get-list', 'CustomerController@getCustomerList')->name('getCustomerList');
	Route::get('/customer/get-form-data/{id?}', 'CustomerController@getCustomerFormData')->name('getCustomerFormData');
	Route::post('/customer/save', 'CustomerController@saveCustomer')->name('saveCustomer');
	Route::get('/customer/delete/{id}', 'CustomerController@deleteCustomer')->name('deleteCustomer');
	Route::get('/customer/session-data', 'CustomerController@getCustonerSessionData')->name('getCustonerSessionData');

});