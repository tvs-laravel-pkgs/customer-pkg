<?php

namespace Abs\CustomerPkg;
use Abs\CustomerPkg\Customer;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use App\State;
use Auth;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CustomerController extends Controller {

	public function __construct() {
	}

	public function getCustomerList() {
		$customer_list = Customer::select(
			'customers.id',
			'customers.code',
			'customers.name',
			'customers.mobile_no',
			'customers.email'
		)
			->where('customers.company_id', Auth::user()->company_id)
			->orderby('customers.id', 'desc');

		return Datatables::of($customer_list)
		// ->addColumn('name', function ($customer_list) {
		// 	$status = $customer_list->status == 'Active' ? 'green' : 'red';
		// 	return '<span class="status-indicator ' . $status . '"></span>' . $customer_list->name;
		// })
			->addColumn('action', function ($customer_list) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/customer-pkg/customer/edit/' . $customer_list->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_customer"
					onclick="angular.element(this).scope().deleteCustomer(' . $customer_list->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getCustomerFormData($id = NULL) {
		if (!$id) {
			$customer = new Customer;
			$address = new Address;
			$action = 'Add';
		} else {
			$customer = Customer::find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['customer'] = $customer;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function getStateList($id) {
		$state_list = Country::getState($id);
		return response()->json($state_list);
	}
	public function getCityList($id) {
		$city_list = State::getCity($id);
		return response()->json($city_list);
	}

	public function saveCustomer(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Customer Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'name.required' => 'Customer Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'mobile_no.required' => 'Mobile Number is Required',
				'mobile_no.max' => 'Maximum 25 Numbers',
				'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				'pincode.required' => 'Pincode is Required',
				'pincode.max' => 'Maximum 6 Characters',
				'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => 'required|max:255|min:3',
				'name' => 'required|max:255|min:3',
				'mobile_no' => 'required|max:25',
				'email' => 'required',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$customer = new Customer;
				$address = new Address;
			} else {
				$customer = Customer::find($request->id);
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$customer->fill($request->all());
			$customer->company_id = Auth::user()->company_id;
			$customer->save();

			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $customer->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Customer Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Customer Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteCustomer($id) {
		$delete_status = Customer::where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
