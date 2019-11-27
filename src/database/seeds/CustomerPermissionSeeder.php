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
			//MASTER > CUSTOMERS
			4600 => [
				'display_order' => 10,
				'parent_id' => null,
				'name' => 'customers',
				'display_name' => 'Customers',
			],
			4601 => [
				'display_order' => 1,
				'parent_id' => 4600,
				'name' => 'add-customer',
				'display_name' => 'Add',
			],
			4602 => [
				'display_order' => 2,
				'parent_id' => 4600,
				'name' => 'edit-customer',
				'display_name' => 'Edit',
			],
			4603 => [
				'display_order' => 3,
				'parent_id' => 4600,
				'name' => 'delete-customer',
				'display_name' => 'Delete',
			],

		];

		foreach ($permissions as $permission_id => $permsion) {
			$permission = Permission::firstOrNew([
				'id' => $permission_id,
			]);
			$permission->fill($permsion);
			$permission->save();
		}
	}
}