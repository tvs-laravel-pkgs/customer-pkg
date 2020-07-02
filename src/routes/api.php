<?php
Route::group(['namespace' => 'App\Http\Controllers\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {


			Route::group(['prefix' => 'customer'], function () {
				$controller = 'Customer';
				Route::get('index', $controller . 'Controller@index');
				Route::get('read/{id}', $controller . 'Controller@read');
				Route::post('save', $controller . 'Controller@save');
				Route::post('save-from-form-data', $controller . 'Controller@saveFromFormData');
				Route::post('save-from-ng-data', $controller . 'Controller@saveFromNgData');
				Route::post('remove', $controller . 'Controller@remove');
				Route::get('options', $controller . 'Controller@options');
			});

		});
	});
});