<?php
namespace Abs\CustomerPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class CustomerPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//CUSTOMERS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'customers',
				'display_name' => 'Customers',
			],
			[
				'display_order' => 1,
				'parent' => 'customers',
				'name' => 'add-customer',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'customers',
				'name' => 'delete-customer',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'customers',
				'name' => 'delete-customer',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}
}