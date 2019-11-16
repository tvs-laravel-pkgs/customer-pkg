<?php

namespace Abs\CustomerPkg;

use Illuminate\Support\ServiceProvider;

class CustomerPkgServiceProvider extends ServiceProvider {
	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register() {
		$this->loadRoutesFrom(__DIR__ . '/routes/web.php');
		$this->loadRoutesFrom(__DIR__ . '/routes/api.php');
		$this->loadMigrationsFrom(__DIR__ . '/database/migrations');
		$this->loadViewsFrom(__DIR__ . '/views', 'customer-pkg');
		$this->publishes([
			__DIR__ . '/views' => base_path('resources/views/customer-pkg'),
			__DIR__ . '/public' => base_path('public'),
		]);
	}

	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot() {
	}
}
