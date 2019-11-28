<?php
Route::group(['namespace' => 'Abs\CustomerPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'customer-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});