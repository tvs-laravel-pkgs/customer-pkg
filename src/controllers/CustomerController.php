<?php

namespace Abs\CustomerPkg;
use Abs\CustomerPkg\Customer;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CustomerController extends Controller {

	public function __construct() {
	}

	public function getCustomerList(Request $request) {
		$customer_list = Customer::withTrashed()
			->select(
				'customers.id',
				'customers.code',
				'customers.name',
				DB::raw('IF(customers.mobile_no IS NULL,"--",customers.mobile_no) as mobile_no'),
				DB::raw('IF(customers.email IS NULL,"--",customers.email) as email'),
				DB::raw('IF(customers.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('customers.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->customer_code)) {
					$query->where('customers.code', 'LIKE', '%' . $request->customer_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->customer_name)) {
					$query->where('customers.name', 'LIKE', '%' . $request->customer_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('customers.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('customers.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('customers.id', 'desc');

		return Datatables::of($customer_list)
			->addColumn('code', function ($customer_list) {
				$status = $customer_list->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $customer_list->code;
			})
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
			$customer = Customer::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['customer'] = $customer;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveCustomer(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Customer Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'Customer Code is already taken',
				'name.required' => 'Customer Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'gst_number.required' => 'GST Number is Required',
				'gst_number.max' => 'Maximum 191 Numbers',
				'mobile_no.max' => 'Maximum 25 Numbers',
				// 'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				// 'pincode.required' => 'Pincode is Required',
				// 'pincode.max' => 'Maximum 6 Characters',
				// 'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:customers,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address' => 'required',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				// 'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$customer = new Customer;
				$customer->created_by_id = Auth::user()->id;
				$customer->created_at = Carbon::now();
				$customer->updated_at = NULL;
				$address = new Address;
			} else {
				$customer = Customer::withTrashed()->find($request->id);
				$customer->updated_by_id = Auth::user()->id;
				$customer->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$customer->fill($request->all());
			$customer->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$customer->deleted_at = Carbon::now();
				$customer->deleted_by_id = Auth::user()->id;
			} else {
				$customer->deleted_by_id = NULL;
				$customer->deleted_at = NULL;
			}
			$customer->gst_number = $request->gst_number;
			$customer->axapta_location_id = $request->axapta_location_id;
			$customer->save();

			if (!$address) {
				$address = new Address;
			}
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
		$delete_status = Customer::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
