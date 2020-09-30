<?php

namespace Abs\CustomerPkg;
use Abs\CustomerPkg\Customer;
use App\Address;
use App\City;
use App\Country;
use App\CustomerDetails;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WpoSoapController;
use App\State;
use Artisaninweb\SoapWrapper\SoapWrapper;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CustomerController extends Controller {

	public function __construct(SoapWrapper $soapWrapper, WpoSoapController $getSoap = null) {
		$this->middleware('auth');
		$this->soapWrapper = $soapWrapper;
		$this->getSoap = $getSoap;
	}

	public function getCustomerFilterData(Request $request) {
		$this->data['extras'] = [
			'state_list' => collect(State::select('id', 'name', 'code')->where('country_id', 1)->get())->prepend(['id' => '', 'name' => 'Select State']),
		];
		return response()->json($this->data);
	}

	public function getCustomerList(Request $request) {
		// dd($request->all());
		// $include_address_filter =
		$customers = Customer::withTrashed()
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

		if (!empty($request->state_id) || !empty($request->city_id)) {
			$customers = $customers->join('addresses', 'addresses.entity_id', 'customers.id')
				->where('addresses.address_of_id', 24) //CUSTOMER
				->where(function ($query) use ($request) {
					if (!empty($request->state_id)) {
						$query->where('addresses.state_id', $request->state_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->city_id)) {
						$query->where('addresses.city_id', $request->city_id);
					}
				})
			;
		}

		return Datatables::of($customers)
			->addColumn('code', function ($customer) {
				$status = $customer->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $customer->code;
			})
			->addColumn('action', function ($customer) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/customer-pkg/customer/edit/' . $customer->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_customer"
					onclick="angular.element(this).scope().deleteCustomer(' . $customer->id . ')" dusk = "delete-btn" title="Delete">
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
			$customer_details = new CustomerDetails;
			$action = 'Add';
		} else {
			$customer = Customer::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			//Add Pan && Aadhar to Customer details by Karthik Kumar on 19-02-2020
			$customer_details = CustomerDetails::where('customer_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			//Add Pan && Aadhar to Customer details by Karthik kumar on 19-02-2020
			if (!$customer_details) {
				$customer_details = new CustomerDetails;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['customer'] = $customer;
		$this->data['address'] = $address;
		$this->data['action'] = $action;
		$this->data['customer_details'] = $customer_details;

		return response()->json($this->data);
	}

	public function saveCustomer(Request $request) {

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
				'gst_number' => 'nullable|max:191',
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
				$customer->credit_limits = $request->credit_limits;
				$customer->credit_days = $request->credit_days;
				$address = new Address;
				$customer_details = new CustomerDetails;
			} else {
				$customer = Customer::withTrashed()->find($request->id);
				$customer->updated_by_id = Auth::user()->id;
				$customer->updated_at = Carbon::now();
				$customer->credit_limits = $request->credit_limits;
				$customer->credit_days = $request->credit_days;
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
				//Add Pan && Aadhar to Customer details by Karthik kumar on 19-02-2020
				$customer_details = CustomerDetails::where('customer_id', $request->id)->first();
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
			//Add Pan && Aadhar to Customer details by Karthik kumar on 19-02-2020
			if (!$customer_details) {
				$customer_details = new CustomerDetails;
			}
			$customer_details->pan_no = $request->pan_no;
			$customer_details->aadhar_no = $request->aadhar_no;
			$customer_details->customer_id = $customer->id;
			$customer_details->save();

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
			$customer_details_delete = CustomerDetail::where('customer_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}

	public function searchCustomer(Request $r) {
		// return Customer::searchCustomer($r);
		// dump(strlen($r->key));
		$key = $r->key;

		$this->soapWrapper->add('customer', function ($service) {
			$service
				->wsdl('https://tvsapp.tvs.in/ongo/WebService.asmx?wsdl')
				->trace(true);
		});
		$params = ['ACCOUNTNUM' => $r->key];
		$getResult = $this->soapWrapper->call('customer.GetNewCustMasterDetails_Search', [$params]);
		$customer_data = $getResult->GetNewCustMasterDetails_SearchResult;
		if (empty($customer_data)) {
			return response()->json(['success' => false, 'error' => 'Customer Not Available!.']);
		}

		// Convert xml string into an object
		$xml_customer_data = simplexml_load_string($customer_data->any);
		// dd($xml_customer_data);
		// Convert into json
		$customer_encode = json_encode($xml_customer_data);

		// Convert into associative array
		$customer_data = json_decode($customer_encode, true);

		$api_customer_data = $customer_data['Table'];
		if (count($api_customer_data) == 0) {
			return response()->json(['success' => false, 'error' => 'Customer Not Available!.']);
		}
		// dd($api_customer_data);
		$list = [];
		if ($api_customer_data) {
			$data = [];
			if (count($api_customer_data) > 0) {
				foreach ($api_customer_data as $key => $customer_data) {
					// $primaryAddress = [];
					$data['code'] = $customer_data['ACCOUNTNUM'];
					$data['name'] = $customer_data['NAME'];
					$data['mobile_no'] = isset($customer_data['LOCATOR']) ? $customer_data['LOCATOR'] : NULL;
					$data['cust_group'] = isset($customer_data['CUSTGROUP']) ? $customer_data['CUSTGROUP'] : NULL;
					$data['pan_number'] = isset($customer_data['PANNO']) ? $customer_data['PANNO'] : NULL;
					// $data['address'] = $customer_data['ADDRESS'];
					// $data['gst_number'] = isset($customer_data['GST_NUMBER']) ? $customer_data['GST_NUMBER'] : NULL;
					// $city = City::select('state_id')->where('name', "LIKE", $customer_data['CITY'])->first();

					// $data['primaryAddress']['state_id'] = $city ? $city->state_id : NULL;
					// dd($data);
					$list[] = $data;
				}
			}
		}

		return response()->json($list);
	}

	public function getCustomerAddress(Request $request) {
		// dd($request->all());
		$this->soapWrapper->add('address', function ($service) {
			$service
				->wsdl('https://tvsapp.tvs.in/ongo/WebService.asmx?wsdl')
				->trace(true);
		});
		$params = ['ACCOUNTNUM' => $request->data['code']];
		$getResult = $this->soapWrapper->call('address.GetNewCustomerAddress_Search', [$params]);
		$customer_data = $getResult->GetNewCustomerAddress_SearchResult;
		if (empty($customer_data)) {
			return response()->json(['success' => false, 'error' => 'Address Not Available!.']);
		}

		// Convert xml string into an object
		$xml_customer_data = simplexml_load_string($customer_data->any);
		// dd($xml_customer_data);

		// Convert into json
		$customer_encode = json_encode($xml_customer_data);
		// Convert into associative array
		$customer_data = json_decode($customer_encode, true);
		// dd($customer_data);
		// dd($customer_data['Table']);

		$api_customer_data = $customer_data['Table'];
		// dd($api_customer_data);
		if (count($api_customer_data) == 0) {
			return response()->json(['success' => false, 'error' => 'Address Not Available!.']);
		}

		$customer = Customer::firstOrNew(['code' => $request->data['code']]);
		$customer->company_id = Auth::user()->company_id;
		$customer->name = $request->data['name'];
		$customer->cust_group = $request->data['cust_group'] == 'Not available' ? NULL : $request->data['cust_group'];
		$customer->gst_number = isset($request->data['gst_number']) ? $request->data['gst_number'] : NULL;
		$customer->pan_number = $request->data['pan_number'] == 'Not available' ? NULL : $request->data['pan_number'];
		$customer->mobile_no = $request->data['mobile_no'] == 'Not available' ? NULL : $request->data['mobile_no'];
		$customer->address = NULL;
		$customer->city = NULL; //$customer_data['CITY'];
		$customer->zipcode = NULL; //$customer_data['ZIPCODE'];
		$customer->created_at = Carbon::now();
		// $customer->save();
		// dd($customer->id);
		// dd($api_customer_data);
		$list = [];
		if ($api_customer_data) {
			$data = [];
			if (isset($api_customer_data)) {
				$array_count = array_filter($api_customer_data, 'is_array');
				if (count($array_count) > 0) {
					// dd('mu;l');
					// $i = 0;
					// dd($api_customer_data);
					foreach ($api_customer_data as $key => $customer_data) {

						$address = Address::firstOrNew(['entity_id' => $customer->id, 'ax_id' => $customer_data['RECID']]); //CUSTOMER
						// dd($address);
						$address->company_id = Auth::user()->company_id;
						$address->entity_id = $customer->id;
						$address->ax_id = $customer_data['RECID'];
						$address->gst_number = isset($customer_data['GST_NUMBER']) ? $customer_data['GST_NUMBER'] : NULL;
						$address->address_of_id = 24;
						$address->address_type_id = 40;
						$address->name = 'Primary Address_' . $customer_data['RECID'];
						$address->address_line1 = str_replace('""', '', $customer_data['ADDRESS']);
						$city = City::where('name', $customer_data['CITY'])->first();
						// if ($city) {
						$state = State::where('code', $customer_data['STATE'])->first();
						$address->country_id = $state ? $state->country_id : NULL;
						$address->state_id = $state ? $state->id : NULL;
						// }
						$address->city_id = $city ? $city->id : NULL;
						$address->pincode = $customer_data['ZIPCODE'] == 'Not available' ? NULL : $customer_data['ZIPCODE'];
						$address->save();
						// dd($address);
						$customer_address[] = $address;
						// $i++;
					}
					// dump($i);
					// dd($customer_get_data);
				} else {
					// dd('sing');
					// dd($api_customer_data['RECID']);
					$address = Address::firstOrNew(['entity_id' => $customer->id, 'ax_id' => $api_customer_data['RECID']]); //CUSTOMER
					// dd($address);
					$address->company_id = Auth::user()->company_id;
					$address->entity_id = $customer->id;
					$address->ax_id = $api_customer_data['RECID'];
					$address->gst_number = isset($api_customer_data['GST_NUMBER']) ? $api_customer_data['GST_NUMBER'] : NULL;
					$address->address_of_id = 24;
					$address->address_type_id = 40;
					$address->name = 'Primary Address_' . $api_customer_data['RECID'];
					$address->address_line1 = str_replace('""', '', $api_customer_data['ADDRESS']);
					$city = City::where('name', $api_customer_data['CITY'])->first();
					// if ($city) {
					$state = State::where('code', $api_customer_data['STATE'])->first();
					$address->country_id = $state ? $state->country_id : NULL;
					$address->state_id = $state ? $state->id : NULL;
					// }
					$address->city_id = $city ? $city->id : NULL;
					$address->pincode = $api_customer_data['ZIPCODE'] == 'Not available' ? NULL : $api_customer_data['ZIPCODE'];
					$address->save();
					// dd($address);
					$customer_address[] = $address;
					// $customer_get_data = Customer::with(['primaryAddresses'])->where('company_id', Auth::user()->company_id)->find($customer->id);
				}
			} else {
				// $customer_get_data = [];
				$customer_address = [];
			}

		}
		return response()->json([
			'success' => true,
			'customer_address' => $customer_address,
			'customer' => $customer,
		]);
	}

	public function getCustomerSave(Request $request) {
		return Customer::getCustomer($request);
	}

}
