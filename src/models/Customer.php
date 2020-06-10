<?php

namespace Abs\CustomerPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Address;
use App\Company;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'customers';
	public $timestamps = true;
	protected $fillable = [
		'code',
		'name',
		'cust_group',
		'dimension',
		'mobile_no',
		'email',
		'company_id',
		'address',
		'gst_number',
		'pan_number',
	];

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$outlet = Outlet::where('code', $record_data->outlet)->where('company_id', $company->id)->first();
		if (!$outlet) {
			$errors[] = 'Invalid outlet : ' . $record_data->outlet;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'code' => $record_data->customer_code,
		]);
		$record->name = $record_data->name;
		$record->mobile_no = $record_data->mobile_number;
		$record->email = $record_data->email;
		$record->address = $record_data->address;
		$record->city = $record_data->city;
		$record->zipcode = $record_data->zipcode;
		$record->business_id = $record_data->business;
		$record->cust_group = $record_data->customer_group;
		$record->dimension = $record_data->dimension;
		$record->outlet_id = $outlet->id;
		$record->save();
		return $record;
	}

	public function city() {
		return $this->belongsTo('App\City', 'city_id');
	}

	public function invoices() {
		return $this->hasMany('Abs\InvoicePkg\Invoice', 'customer_id'); //->where('entity_type_id',);
	}

	public function state() {
		return $this->belongsTo('App\State', 'state_id');
	}
	public function getFormattedAddress() {
		$customer = $this;
		if (!$customer->address) {
			return 'N/A';
		}
		$formatted_address = '';
		$formatted_address .= !empty($customer->address) ? $customer->address : '';
		$formatted_address .= $customer->city ? ', ' . $customer->city : '';
		$formatted_address .= $customer->zipcode ? ', ' . $customer->zipcode : '';
		return $formatted_address;

	}

	public static function searchCustomer($r) {
		$key = $r->key;
		$list = self::where('company_id', Auth::user()->company_id)
			->select(
				'id',
				'name',
				'code'
			)
			->where(function ($q) use ($key) {
				$q->where('name', 'like', $key . '%')
					->orWhere('code', 'like', $key . '%')
					->orWhere('mobile_no', 'like', $key . '%')
				;
			})
			->get();
		return response()->json($list);
	}

	public static function getCustomer($request) {
		$customer = self::find($request->id);

		if (!$customer) {
			return response()->json(['success' => false, 'error' => 'Customer not found']);
		}
		return response()->json([
			'success' => true,
			'customer' => $customer,
		]);
	}

	public function addresses() {
		return $this->hasMany('App\Address', 'entity_id')->where('address_of_id', 24);
	}

	public function primaryAddress() {
		return $this->hasOne('App\Address', 'entity_id')->where('address_of_id', 24)->where('address_type_id', 40)
		;
	}

	public function address() {
		return $this->hasOne('App\Address', 'entity_id')->where('address_of_id', 24)->where('address_type_id', 40)
		;
	}

	public function customerDetail() {
		return $this->hasOne('App\CustomerDetail');
	}

	public static function saveCustomer($values) {
		if (!$values['id']) {
			//NEW CUSTOMER
			$customer = new self;
			$customer->company_id = Auth::user()->company_id;
			$customer->created_by_id = Auth::id();
		} else {
			$customer = self::find($values['id']);
			$customer->updated_by_id = Auth::id();
		}
		$customer->fill($values);
		if (!isset($values['code']) || !$values['code']) {
			$customer->code = rand();
		}
		$customer->save();
		if (!isset($values['code']) || !$values['code']) {
			$customer->code = $customer->id;
		}
		return $customer;

	}
	public function saveAddress($values) {
		$address = Address::firstOrNew([
			'company_id' => $this->company_id,
			'address_of_id' => 24, //CUSTOMER
			'entity_id' => $this->id,
			'address_type_id' => 40, //PRIMARY ADDRESS
		]);
		$address->fill($values);
		$address->save();
		return $address;
	}

}
