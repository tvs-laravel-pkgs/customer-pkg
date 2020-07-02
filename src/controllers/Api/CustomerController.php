<?php

namespace Abs\CustomerPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Customer;
use App\Http\Controllers\Controller;

class CustomerController extends Controller {
	use CrudTrait;
	public $model = Customer::class;
	public $successStatus = 200;

	private function beforeCrudAction($action, $response, $customer) {
		if ($action == 'read') {
			$address = $customer->primaryAddress;
			if ($address) {
				$address->formatted = $address->getFormattedAddress();
			}
			// dd($customer->primaryAddress->formatted);
			// $response->setData('customer', $customer);
		}
	}

	private function alterCrudResponse($action, $response) {
		if ($action == 'read') {
			// $customer = $response->getData('customer');
			// $address = $customer->address;
			// dd()
			// if ($address) {
			// 	$address->formatted_address = $customer->getFormattedAddress();
			// }
			// dd($customer->address->formatted_address);
			// $response->setData('customer', $customer);
		}
	}

}