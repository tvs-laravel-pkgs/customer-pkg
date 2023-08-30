<?php

namespace Abs\CustomerPkg;
use Abs\CustomerPkg\Customer;
use App\Address;
use App\City;
use App\Config;
use App\Country;
use App\CustomerDetails;
use App\Sbu;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WpoSoapController;
use App\Outlet;
use App\State;
use Abs\CustomerPkg\CustomerDimension;
use Artisaninweb\SoapWrapper\SoapWrapper;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Entrust;
use Yajra\Datatables\Datatables;

class CustomerController extends Controller {

	public function __construct(WpoSoapController $getSoap = null) {
		$this->middleware('auth');
		$this->soapWrapper = new SoapWrapper;
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
			// ->where('customers.company_id', Auth::user()->company_id)
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
		//List All customer by Karthick T on 09-02-2021
		if (!Entrust::can('view-all-customers'))
			$customers = $customers->where('customers.company_id', Auth::user()->company_id);
		//List All customer by Karthick T on 09-02-2021

		return Datatables::of($customers)
			->addColumn('code', function ($customer) {
				$status = $customer->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $customer->code;
			})
			->addColumn('action', function ($customer) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				$action = '';
				$action .= '<a href="#!/customer-pkg/customer/edit/' . $customer->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>';
				if (Entrust::can('delete-customer')) {
						$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_customer"
							onclick="angular.element(this).scope().deleteCustomer(' . $customer->id . ')" dusk = "delete-btn" title="Delete">
							<img src="' . $delete_img . '" alt="delete" class="img-responsive">
						</a>';
				}
				return $action;
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
			// $address = Address::select('address_of_id', 'entity_id', 'address_type_id', 'name', 'address_line1', 'address_line2', 'country_id', 'state_id', 'city_id', 'pincode','gst_number')->where('address_of_id', 24)->where('entity_id', $id)->first();

			$customer_primary_address = Address::where('company_id', $customer->company_id)
                ->where('entity_id', $customer->id)
                ->where('address_of_id', 24)
                ->where('address_type_id',40)
                ->where('is_primary',1)
                ->orderBy('id', 'DESC')
                ->first();
            $customer_non_primary_address = Address::where('company_id', $customer->company_id)
                ->where('entity_id', $customer->id)
                ->where('address_of_id', 24)
                ->where('address_type_id',40)
                ->where(function($q) {
                    $q->where('is_primary', 0)
                    ->orWhereNull('is_primary');
                })
                ->orderBy('id', 'DESC')
                ->first();

            if(!empty($customer_primary_address)){
            	$address = $customer_primary_address;
            }else{
            	$address = $customer_non_primary_address;
            }

			//Add Pan && Aadhar to Customer details by Karthik Kumar on 19-02-2020
			$customer_details = CustomerDetails::select('customer_id', 'pan_no', 'aadhar_no')->where('customer_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			//Add Pan && Aadhar to Customer details by Karthik kumar on 19-02-2020
			if (!$customer_details) {
				$customer_details = new CustomerDetails;
			}
			$action = 'Edit';
			//Dimension id by Karthick T on 08-02-2021
			$dimension= CustomerDimension::where('customer_id',$id)
				->where('company_id',Auth::user()->company_id)
				->pluck('dimension')->first();
			$customer->dimension = $dimension;
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['pdf_format_list'] = Collect(Config::select('id', 'name')->where('config_type_id', 420)->get())->prepend(['id' => '', 'name' => 'Select PDF Formate']);
		$this->data['customer'] = $customer;
		$this->data['address'] = $address;
		$this->data['action'] = $action;
		$this->data['customer_details'] = $customer_details;

		//Outlet by Karthick T on 23-10-2020
		$this->data['outlet_list'] = $outlet_list = Collect(
			Outlet::select(
				'id',
				'code'
			)->where('company_id', Auth::user()->company_id)
				->get()
		)->prepend(['id' => '', 'code' => 'Select Outlet']);
        //IMS Type by Parthiban V on 29-07-2021
        $this->data['ims_type_list'] =  Collect(Config::select('id', 'name')->where('config_type_id', 254)->get())->prepend(['id' => '', 'name' => 'Select IMS Type']);
		$this->data['sbu_list'] = Collect(Sbu::select(DB::raw('CONCAT(IFNULL(sbus.name,""), " / ", IFNULL(sbus.oracle_code,"")) as name'), 'id')
				->where('company_id', Auth::user()->company_id)
				->get()
			)->prepend(['id' => '', 'name' => 'Select LOB']);

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
				'street.max' => 'Maximum 255 Characters',
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
				'street' => 'max:255',
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
				$customer->pan_number = $request->pan_no;
				$address = new Address;
				$customer_details = new CustomerDetails;
			} else {
				$customer = Customer::withTrashed()->find($request->id);
				//dd($customer->gst_number);
				$customer->updated_by_id = Auth::user()->id;
				$customer->updated_at = Carbon::now();
				$customer->credit_limits = $request->credit_limits;
				$customer->credit_days = $request->credit_days;
				$customer->pan_number = $request->pan_no;
				//$address = Address::select('address_of_id', 'entity_id', 'address_type_id', 'name', 'address_line1', 'address_line2', 'country_id', 'state_id', 'city_id', 'pincode')->where('address_of_id', 24)->where('entity_id', $request->id)->first();
				// $address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
				$address = Address::where('id', $request->address_id)->first();
				// $address->address_line1 = $request->address_line1;
				// $address->pincode = $request->pincode;
				// $address->gst_number = $request->gst_number;
				//Add Pan && Aadhar to Customer details by Karthik kumar on 19-02-2020
				$customer_details = CustomerDetails::select('customer_id', 'pan_no', 'aadhar_no')->where('customer_id', $request->id)->first();			
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
			//Outlet by Karthick T on 23-10-2020
			$customer->outlet_id = $request->outlet_id;
			//Customer cash limit by Karthick T on 14-12-2020
			$customer->cash_limit_status = (isset($request->customer_limit_allow) && $request->customer_limit_allow) ? $request->customer_limit_allow : 0;
            //IMS Type By Parthiban V on 29-07-2021
            $customer->ims_type_id = (isset($request->ims_type_id) && $request->ims_type_id) ? $request->ims_type_id : null;			
			if (isset($request->lob_id))
				$customer->lob_id = $request->lob_id;
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
			$address->gst_number = $request->gst_number;
			$address->street = $request->street;
			$address->save();
			//Add Pan && Aadhar to Customer details by Karthik kumar on 19-02-2020
			if (!$customer_details) {
				$customer_details = new CustomerDetails;
			}
			$customer_details->pan_no = $request->pan_no;
			$customer_details->aadhar_no = $request->aadhar_no;
			$customer_details->customer_id = $customer->id;
			$customer_details->save();

			//Store Customer Dimension By Karthick T on 08-02-2021
			if(isset($request->dimension) && $request->dimension){
				$customer_dimension = CustomerDimension::firstOrNew([
					'customer_id' => $customer->id,
					'company_id' => Auth::user()->company_id,
				]);
				$customer_dimension->dimension = $request->dimension;
				$customer_dimension->created_by_id = Auth::user()->id;
				$customer_dimension->updated_by_id = Auth::user()->id;
				$customer_dimension->save();
			}else{
				$customer_dimension = CustomerDimension::where('customer_id',$customer->id)
					->where('company_id', Auth::user()->company_id)
					->forcedelete();
			}
			//Store Customer Dimension By Karthick T on 08-02-2021
			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Customer Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Customer Details Updated Successfully']]);
			}
		} catch (\Exception $e) {
			DB::rollBack();
            return response()->json([
                'success' => false,
                'errors' => [
                    'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
                ],
            ]);
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

	public function searchCustomerPkg(Request $r) {
		// return Customer::searchCustomer($r);
		// dd(strlen($r->key));
		try {
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
			if (isset($api_customer_data)) {
				$data = [];
				$array_count = array_filter($api_customer_data, 'is_array');
				if (count($array_count) > 0) {
					// if (count($api_customer_data) > 0) {
					foreach ($api_customer_data as $key => $customer_data) {
						$data['code'] = $customer_data['ACCOUNTNUM'];
						$data['name'] = $customer_data['NAME'];
						$data['mobile_no'] = isset($customer_data['LOCATOR']) && $customer_data['LOCATOR'] != 'Not available' ? $customer_data['LOCATOR'] : NULL;
						$data['cust_group'] = isset($customer_data['CUSTGROUP']) && $customer_data['CUSTGROUP'] != 'Not available' ? $customer_data['CUSTGROUP'] : NULL;
						$data['pan_number'] = isset($customer_data['PANNO']) && $customer_data['PANNO'] != 'Not available' ? $customer_data['PANNO'] : NULL;

						$list[] = $data;
					}
				} else {
					$data['code'] = $api_customer_data['ACCOUNTNUM'];
					$data['name'] = $api_customer_data['NAME'];
					$data['mobile_no'] = isset($api_customer_data['LOCATOR']) && $api_customer_data['LOCATOR'] != 'Not available' ? $api_customer_data['LOCATOR'] : NULL;
					$data['cust_group'] = isset($api_customer_data['CUSTGROUP']) && $api_customer_data['CUSTGROUP'] != 'Not available' ? $api_customer_data['CUSTGROUP'] : NULL;
					$data['pan_number'] = isset($api_customer_data['PANNO']) && $api_customer_data['PANNO'] != 'Not available' ? $api_customer_data['PANNO'] : NULL;

					$list[] = $data;
				}
			}
			return response()->json($list);
		} catch (\SoapFault $e) {
			return response()->json(['success' => false, 'error' => 'Somthing went worng in SOAP Service!']);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'error' => 'Somthing went worng!']);
		}

	}

	public function getCustomerAddressPkg(Request $request) {
		// dd($request->all());
		try {
			$customer_detail = [];
			$customer_details = $this->getSoap->getCustomerAddressDetails($request->data['code']);
			// dump($customer_details);
			if ($customer_details) {
				$customer = Customer::firstOrNew([
					'code' => $request->data['code'],
				]);
				// dd($customer);
				$city_id = City::where('name', $customer_details['city'])->pluck('id')->first();
				$customer->company_id = Auth::user()->company_id;
				$customer->name = $request->data['name'];
				$customer->cust_group = $request->data['cust_group'];
				$customer->address = $customer_details['address'];
				$customer->zipcode = $customer_details['pincode'];
				$customer->pan_number = $request->data['pan_number'];
				$customer->mobile_no = $request->data['mobile_no']; //phone number
				$customer->city = $customer_details['city'];
				$customer->city_id = $city_id ? $city_id : NULL;
				$customer->company_id = Auth::user()->company_id;
				$customer->save();

				$customer_detail = Customer::find($customer->id);
			}
			return response()->json([
				'success' => true,
				'customer' => $customer_detail,
			]);

		} catch (Exception $e) {
			return response()->json(['success' => false, 'error' => 'Somthing went worng!']);
		}
	}

	public function getCustomerSave(Request $request) {
		return Customer::getCustomer($request);
	}

}
