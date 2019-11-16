<?php
Route::group(['namespace' => 'Abs\TaxPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'tax-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});