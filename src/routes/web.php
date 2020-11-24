<?php

Route::group(['namespace' => 'Abs\CustomerPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'customer-pkg'], function () {

	//CUSTOMERS
	Route::get('/customers/get-list', 'CustomerController@getCustomerList')->name('getCustomerList');
	Route::get('/customer/get-form-data/{id?}', 'CustomerController@getCustomerFormData')->name('getCustomerFormData');
	Route::post('/customer/save', 'CustomerController@saveCustomer')->name('saveCustomer');
	Route::get('/customer/delete/{id}', 'CustomerController@deleteCustomer')->name('deleteCustomer');

	Route::post('/customer/search', 'CustomerController@searchCustomer')->name('searchCustomer');
	Route::post('/customer/get', 'CustomerController@getCustomer')->name('getCustomer');

	//USED SOAP API AX CUSTOMER GET
	Route::post('/customer/search/pkg', 'CustomerController@searchCustomerPkg')->name('searchCustomerPkg');
	Route::post('/customer/get/pkg', 'CustomerController@getCustomerAddressPkg')->name('getCustomerAddressPkg');

	Route::get('/customer/get-filter-data', 'CustomerController@getCustomerFilterData')->name('getCustomerFilterData');

});