<?php
namespace Abs\CustomerPkg\Database\Seeds;

use Abs\CustomerPkg\Customer;
use Abs\LocationPkg\State;
use App\Address;
use Excel;
use Illuminate\Database\Seeder;

class ExcelCustomerImportPkgSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		// dd(3);
		$states = Excel::selectSheetsByIndex(0)->load('public/excel-imports/CNDN Customer Master.xlsx', function ($reader) {
			$reader->limitRows(2000);
			$reader->limitColumns(4);
			$records = $reader->get();
			foreach ($records as $key => $record) {
				try {
					$errors = [];
					if (empty($record->account_no)) {
						$errors[] = 'Account No is Empty';
					}
					if (empty($record->name)) {
						$errors[] = 'Name is Empty';
					}
					if (empty($record->state)) {
						$errors[] = 'State is Empty';
					} else {
						$state = State::where([
							'code' => $record->state,
						])->first();
						if (!$state) {
							$errors[] = 'State Code not found: ' . $record->state;
						}
					}
					if (empty($record->address)) {
						$errors[] = 'Address is Empty';
					}

					if (!empty($errors)) {
						dump($key + 1, $errors, $record);
						continue;
					}

					$customer = Customer::firstOrNew([
						'code' => $record->account_no,
					]);
					$customer->code = $record->account_no;
					$customer->name = $record->name;
					$customer->state_id = $state->id;
					$customer->address = $record->address;
					$customer->company_id = 1; // TVS & Sons Pvt Ltd.
					$customer->save();

					$address = Address::firstOrNew([
						'entity_id' => $customer->id,
						'company_id' => $customer->company_id,
						'address_of_id' => 24,
					]);
					$address->company_id = $customer->company_id;
					$address->address_of_id = 24; // Address of Customer
					$address->entity_id = $customer->id;
					$address->address_type_id = 40; // Primary Address
					$address->name = 'Primary Address';
					$address->address_line1 = '';
					$address->state_id = $customer->state_id;
					$address->save();

				} catch (\Exception $e) {
					dump($record, $e->getMessage());
				}
			}
		});
	}
}