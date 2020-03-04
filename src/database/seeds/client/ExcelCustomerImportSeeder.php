<?php
use Illuminate\Database\Seeder;

class ExcelCustomerImportSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$this->call(Abs\CustomerPkg\Database\Seeds\ExcelCustomerImportPkgSeeder::class);
	}
}