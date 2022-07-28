<?php

namespace Abs\CustomerPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Address;
use App\ApiLog;
use App\BaseModel;
use App\Company;
use App\Outlet;
use App\Receipt;
use App\State;
use Auth;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Database\Eloquent\Model;
use phpseclib\Crypt\RSA as Crypt_RSA;

class Customer extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'customers';
	public static $AUTO_GENERATE_CODE = false;
	public $timestamps = true;
	public static $ADDRESS_OF_ID = 24;
	public static $PRIMARY_ADDRESS_TYPE_ID = 40;
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
		'pdf_format_id',
	];

	protected static $excelColumnRules = [
		'Name' => [
			'table_column_name' => 'name',
			'rules' => [
				'required' => [
				],
			],
		],
		'Code' => [
			'table_column_name' => 'code',
			'rules' => [
				'required' => [
				],
			],
		],
		'Mobile Number' => [
			'table_column_name' => 'mobile_no',
			'rules' => [
				// 'mobile_number' => [],
			],
		],
		'Email' => [
			'table_column_name' => 'email',
			'rules' => [

			],
		],
		'GST Number' => [
			'table_column_name' => 'gst_number',
			'rules' => [

			],
		],
		'Business Code' => [
			'table_column_name' => 'business_id',
			'rules' => [

			],
		],
		'Outlet Code' => [
			'table_column_name' => 'outlet_id',
			'rules' => [
				'fk' => [
					'class' => 'App\Outlet',
					'foreign_table_column' => 'code',
					'check_with_company' => true,
				],
			],
		],
		'Customer Group Name' => [
			'table_column_name' => 'cust_group',
			'rules' => [

			],
		],
		'Dimension' => [
			'table_column_name' => 'dimension',
			'rules' => [

			],
		],
		'Pan Number' => [
			'table_column_name' => 'pan_number',
			'rules' => [

			],
		],
	];

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			'Mobile Number' => $record_data->mobile_number,
			'Email' => $record_data->email,
			'GST Number' => $record_data->gst_number,
			'Business Code' => $record_data->business_code,
			'Outlet Code' => $record_data->outlet_code,
			'Customer Group Name' => $record_data->customer_group_name,
			'Dimension' => $record_data->dimension,
			'Pan Number' => $record_data->pan_number,
		];
		return static::saveFromExcelArray($record);
	}

	public static function saveFromExcelArray($record_data) {
		$errors = [];
		$company = Company::where('code', $record_data['Company Code'])->first();
		if (!$company) {
			return [
				'success' => false,
				'errors' => ['Invalid Company : ' . $record_data['Company Code']],
			];
		}

		if (!isset($record_data['created_by_id'])) {
			$admin = $company->admin();

			if (!$admin) {
				return [
					'success' => false,
					'errors' => ['Default Admin user not found'],
				];
			}
			$created_by_id = $admin->id;
		} else {
			$created_by_id = $record_data['created_by_id'];
		}

		$outlet_id = null;
		if (!empty($record_data['Outlet Code'])) {

			$outlet = Outlet::where([
				'company_id' => $company->id,
				'code' => $record_data['Outlet Code'],
			])->first();
			if (!$outlet) {
				$errors[] = 'Invalid Outlet Code : ' . $record_data['Outlet Code'];
			} else {
				$outlet_id = $outlet->id;
			}
		}

		$business_id = null;
		if (!empty($record_data['Business Code'])) {

			$business = Outlet::where([
				'company_id' => $company->id,
				'code' => $record_data['Business Code'],
			])->first();
			if (!$business) {
				$errors[] = 'Invalid Business Code : ' . $record_data['Business Code'];
			} else {
				$business_id = $business->id;
			}
		}

		if (count($errors) > 0) {
			return [
				'success' => false,
				'errors' => $errors,
			];
		}
		$record = self::firstOrNew([
			'company_id' => $company->id,
			'code' => $record_data['Code'],
		]);

		$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
		if (!$result['success']) {
			return $result;
		}
		$record->business_id = $business_id;
		$record->outlet_id = $outlet_id;
		$record->cust_group = null;
		$record->company_id = $company->id;
		$record->created_by_id = $created_by_id;
		$record->save();
		return [
			'success' => true,
		];
	}

	protected $appends = [
		// 'formatted_address',
	];

	// protected $visible = [
	// 	'id',
	// 	'first_name',
	// 	'last_name',
	// 	'display_name',
	// 	'email',
	// 	'contact_email',
	// 	'token',
	// 	'address',
	// 	'dob',
	// 	'phone',
	// 	'data_captured',
	// 	'activated',
	// ];

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function ($query) use ($term) {
				$query->where('name', 'like', $term . '%')
					->orWhere('code', 'like', $term . '%');
			});
		}
	}

	// Getter & Setters --------------------------------------------------------------

	public function getFormattedAddressAttribute() {
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

	// Relations --------------------------------------------------------------

	public function addresses() {
		return $this->hasMany('App\Address', 'entity_id')->where('address_of_id', static::$ADDRESS_OF_ID);
	}

	public function primaryAddress() {
		return $this->hasOne('App\Address', 'entity_id')->where('address_of_id', static::$ADDRESS_OF_ID)->where('address_type_id', static::$PRIMARY_ADDRESS_TYPE_ID)
		;
	}

	public function address() {
		return $this->hasOne('App\Address', 'entity_id')->where('address_of_id', static::$ADDRESS_OF_ID)->where('address_type_id', static::$PRIMARY_ADDRESS_TYPE_ID)
		;
	}

	public function receipt()
    {
        return $this->morphMany(Receipt::class, 'receiptable');
    }

	public static function relationships($action = '') {
		if ($action == 'options') {
			$relationships = [
			];
		} else if ($action == 'index') {
			$relationships = [
			];
		} else {
			$relationships = [
				'primaryAddress',
				'primaryAddress.city',
				'primaryAddress.state',
				'primaryAddress.country',
			];
		}

		return $relationships;
	}

	public static function selectableFields($type = '') {
		if ($type == 'options') {
			return [
				'id',
				'code',
				'name',
			];
		}
	}

	/*public static function createFromObject($record_data) {

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
	}*/

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
		$list = self::with(['primaryAddress'])->where('company_id', Auth::user()->company_id)
			->select(
				'id',
				'name',
				'code',
				'gst_number',
				'pan_number',
				'mobile_no',
				'email'
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
		// $customer = self::find($request->id);
		$customer = self::with(['primaryAddress'])->where('company_id', Auth::user()->company_id)
			->find($request->id);

		if (!$customer) {
			return response()->json(['success' => false, 'error' => 'Customer not found']);
		}
		return response()->json([
			'success' => true,
			'customer' => $customer,
		]);
	}

	public static function saveCustomer($values) {
		// if (!$values['customer_id']) {
		// 	//NEW CUSTOMER
		// 	$customer = new Customer;
		// 	$customer->company_id = Auth::user()->company_id;
		// 	$customer->created_by_id = Auth::id();
		// } else {
		// 	$customer = Customer::find($values['customer_id']);
		// 	$customer->updated_by_id = Auth::id();
		// }
		// if (!isset($values['code']) || !$values['code']) {
		// 	$customer->code = rand();
		// }
		$customer = Customer::firstOrNew(['code' => $values['code']]);
		$customer->fill($values);
		$customer->save();
		// if (!isset($values['code']) || !$values['code']) {
		// 	$customer->code = $customer->id;
		// }
		return $customer;

	}
	public function saveAddress($values) {
		$address = Address::firstOrNew([
			'company_id' => Auth::user()->company_id,
			'address_of_id' => 24, //CUSTOMER
			'entity_id' => $this->id,
			'address_type_id' => 40, //PRIMARY ADDRESS
		]);
		$address->fill($values);
		$address->save();
		return $address;
	}

	public function invoices() {
		return $this->hasMany('Abs\InvoicePkg\Invoice', 'customer_id'); //->where('entity_type_id',);
	}

	public static function getGstDetail($gstin) {
		// dd($gstin);
		$errors = [];
		if (!$gstin) {
			return response()->json([
				'success' => false,
				'error' => 'GSTIN is Empty!',
			]);
		}
		// $rsa = new Crypt_RSA;
		// $encrypter = app('Illuminate\Contracts\Encryption\Encrypter');

		// $public_key = 'MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxqHazGS4OkY/bDp0oklL+Ser7EpTpxyeMop8kfBlhzc8dzWryuAECwu8i/avzL4f5XG/DdSgMz7EdZCMrcxtmGJlMo2tUqjVlIsUslMG6Cmn46w0u+pSiM9McqIvJgnntKDHg90EIWg1BNnZkJy1NcDrB4O4ea66Y6WGNdb0DxciaYRlToohv8q72YLEII/z7W/7EyDYEaoSlgYs4BUP69LF7SANDZ8ZuTpQQKGF4TJKNhJ+ocmJ8ahb2HTwH3Ol0THF+0gJmaigs8wcpWFOE2K+KxWfyX6bPBpjTzC+wQChCnGQREhaKdzawE/aRVEVnvWc43dhm0janHp29mAAVv+ngYP9tKeFMjVqbr8YuoT2InHWFKhpPN8wsk30YxyDvWkN3mUgj3Q/IUhiDh6fU8GBZ+iIoxiUfrKvC/XzXVsCE2JlGVceuZR8OzwGrxk+dvMnVHyauN1YWnJuUTYTrCw3rgpNOyTWWmlw2z5dDMpoHlY0WmTVh0CrMeQdP33D3LGsa+7JYRyoRBhUTHepxLwk8UiLbu6bGO1sQwstLTTmk+Z9ZSk9EUK03Bkgv0hOmSPKC4MLD5rOM/oaP0LLzZ49jm9yXIrgbEcn7rv82hk8ghqTfChmQV/q+94qijf+rM2XJ7QX6XBES0UvnWnV6bVjSoLuBi9TF1ttLpiT3fkCAwEAAQ=='; //PROVIDE FROM BDO COMPANY

		// // $clientid = "prakashr@featsolutions.in"; //PROVIDE FROM BDO COMPANY
		// // $clientid = "amutha@sundarammotors.com"; //PROVIDE FROM BDO COMPANY
		// $clientid = "61b27a26bd86cbb93c5c11be0c2856"; //LIVE

		// // dump('clientid ' . $clientid);

		// $rsa->loadKey($public_key);
		// $rsa->setEncryptionMode(2);
		// // $client_encryption_key = 'BBAkBDB0YzZiYThkYTg4ZDZBBDJjZBUyBGFkBBB0BWB='; // CLIENT SECRET KEY
		// // $client_encryption_key = 'TQAkSDQ0YzZiYTTkYTg4ZDZSSDJjZSUySGFkSSQ0SWQ='; // CLIENT SECRET KEY
		// $client_encryption_key = '7dd55886594bccadb03c48eb3f448e'; // LIVE

		// $ClientSecret = $rsa->encrypt($client_encryption_key);
		// $clientsecretencrypted = base64_encode($ClientSecret);
		// // dump('ClientSecret ' . $clientsecretencrypted);

		// $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		// $app_secret_key = substr(str_shuffle($characters), 0, 32); // RANDOM KEY GENERATE
		// // $app_key_data = 'Rdp5EB5w756dVph0C3jCXY1K6RPC6RCD'; // RANDOM KEY GENERATE
		// // dump($app_key_data);
		// $AppSecret = $rsa->encrypt($app_secret_key);
		// $appsecretkey = base64_encode($AppSecret);
		// // dump('appsecretkey ' . $appsecretkey);

		// // $bdo_login_url = 'https://sandboxeinvoiceapi.bdo.in/bdoauth/bdoauthenticate';
		// $bdo_login_url = 'https://einvoiceapi.bdo.in/bdoauth/bdoauthenticate'; //LIVE

		// $ch = curl_init($bdo_login_url);
		// // Setup request to send json via POST`
		// $params = json_encode(array(
		// 	'clientid' => $clientid,
		// 	'clientsecretencrypted' => $clientsecretencrypted,
		// 	'appsecretkey' => $appsecretkey,
		// ));

		// // Attach encoded JSON string to the POST fields
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

		// // Set the content type to application/json
		// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

		// // Return response instead of outputting
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// // Execute the POST request
		// $server_output_data = curl_exec($ch);

		// // Get the POST request header status
		// $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// // dd($status);
		// // dd($server_output_data);
		// // If header status is not Created or not OK, return error message
		// if ($status != 200) {
		// 	$errors[] = 'Connection Error!';
		// 	return response()->json(['success' => false, 'error' => 'Connection Error!']);
		// }

		// curl_close($ch);

		// $server_output = json_decode($server_output_data);

		// $clientid = "61b27a26bd86cbb93c5c11be0c2856"; //LIVE
		$clientid = config('custom.CLIENT_ID');
		$authToken = getBdoAuthToken(Auth::user()->company_id);
		$params = isset($authToken["params"])?$authToken["params"]:json_encode([]);
		$server_output_data = isset($authToken["server_output"])?json_decode($authToken["server_output"]):json_encode([]);

		DB::beginTransaction();

		$api_log = new ApiLog;
		$api_log->type_id = 24; //Customer
		$api_log->entity_number = $gstin;
		$api_log->entity_id = NULL;
		$api_log->url = $authToken["url"];
		$api_log->src_data = $params;
		$api_log->response_data = json_encode($server_output_data);
		$api_log->user_id = Auth::user()->id;
		$api_log->status_id = (!$authToken["success"]) ? 11272 : 11271; //Failed //Success
		$api_log->errors = (!$authToken["success"] && !empty($authToken['errors'])) ? json_encode($authToken['errors']) : NULL;
		$api_log->created_by_id = Auth::user()->id;
		$api_log->save();

		DB::commit();

		// dd($server_output_data->status);
		if (!$authToken["success"]) {
			$errors[] = 'BDO Login Failed. Please Try again later!!';
			return response()->json([
				'success' => false,
				'error' => $authToken['errors'],
				'errors' => $errors,
				'api_log'=>$api_log
			]);
		}

		$app_secret_key = $authToken["result"]["app_secret"];
        $expiry = $authToken["result"]["expiry_date"];
        $bdo_authtoken = $authToken["result"]["bdo_authtoken"];
        $status = $authToken["result"]["status"];
        //DECRYPTED BDO SEK KEY
        $decrypt_data_with_bdo_sek = $authToken["result"]["bdo_secret"];

		// if (!$decrypt_data_with_bdo_sek) {
		// 	$errors[] = 'Decryption Error!';
		// 	return response()->json(['success' => false, 'error' => 'Decryption Error!']);
		// }
		//$bdo_check_gstin_url = 'https://einvoiceapi.bdo.in/bdoapi/public/syncGstinDetailsFromCP/' . $gstin; //LIVE
		$bdo_check_gstin_url = 'https://einvoiceapi.bdo.in/bdoapi/public/getgstinDetails/' . $gstin;
		// $bdo_check_gstin_url = 'https://sandboxeinvoiceapi.bdo.in/bdoapi/public/getgstinDetails/' . $gstin; //UAT

		// $bdo_check_gstin_url = 'https://sandboxeinvoiceapi.bdo.in/bdoapi/public/getgstinDetails/' . $gstin;
		// $bdo_check_gstin_url = 'https://einvoiceapi.bdo.in/bdoapi/public/getgstinDetails/' . $gstin; //LIVE
		// dd($bdo_check_gstin_url);

		$ch = curl_init($bdo_check_gstin_url);
		// Setup request to send json via POST`

		// Attach encoded JSON string to the POST fields
		curl_setopt($ch, CURLOPT_URL, $bdo_check_gstin_url);

		// Set the content type to application/json
		$params = json_encode(array(
			'Content-Type' => 'application/json',
			'client_id' => $clientid,
			'bdo_authtoken' => $bdo_authtoken,
			// 'gstin: ' . $r->outlet_gstin,
			// 'gstin' => '33AABCT0159K1ZG',
			'gstin' => '33AAGCT6376B1ZF',
		));
		// dd($params);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'client_id:' . $clientid,
			'bdo_authtoken:' . $bdo_authtoken,
			// 'gstin: ' . $r->outlet_gstin,
			// 'gstin:33AABCT0159K1ZG',
			'gstin:33AAGCT6376B1ZF',
		));

		// Return response instead of outputting
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Execute the POST request
		$get_gstin_output_data = curl_exec($ch);
		// dd($get_gstin_output_data);
		$get_gstin_output = json_decode($get_gstin_output_data);
		if($get_gstin_output_data == 'GSP AUTHTOKEN IS NOT VALID'){
			DB::table('bdo_auth_tokens')->where([
				'bdo_secret'=>$decrypt_data_with_bdo_sek,
				'app_secret'=>$app_secret_key])
			->update(['status'=>'0']);
			// return [
   //              'success' => false,
   //              'error' => "GSP AUTHTOKEN IS NOT VALID, TRY AGAIN",
   //              'errors' => []
   //          ];

			return response()->json([
				'success' => false,
				'error' => "GSP AUTHTOKEN IS NOT VALID, TRY AGAIN",
				'errors' => [],
			]);
		}

		DB::beginTransaction();

		$api_log = new ApiLog;
		$api_log->type_id = 24; //CUSTOMER
		$api_log->entity_number = $gstin;
		$api_log->entity_id = NULL;
		$api_log->url = $bdo_check_gstin_url;
		$api_log->src_data = $params;
		$api_log->response_data = $get_gstin_output_data;
		$api_log->user_id = Auth::user()->id;
		$api_log->status_id = $get_gstin_output->Status == 0 ? 11272 : 11271; //FAILED //SUCCESS
		$api_log->errors = $get_gstin_output->Status == 0 ? $get_gstin_output->Error : NULL;
		$api_log->created_by_id = Auth::user()->id;
		$api_log->save();

		DB::commit();

		if ($get_gstin_output->Status == 0) {
			$errors[] = 'Something went on Server.Please Try again later!!';
			return response()->json([
				'success' => false,
				'error' => $get_gstin_output->Error,
				'errors' => $errors,
			]);
		}
		curl_close($ch);

		//AES DECRYPTION AFTER GENERATE IRN (DECRYPT WITH DECRYPT ENCODED DATA FROM AES DECRYPTION AND GSTIN DATA RESPONSE)
		$gstin_decrypt_data = self::decryptAesData($decrypt_data_with_bdo_sek, $get_gstin_output->Data);
		if (!$gstin_decrypt_data) {
			$errors[] = 'Decryption Error!';
			return response()->json(['success' => false, 'error' => 'Decryption Error!']);
		}
		// dump($gstin_decrypt_data);
		$gst_validate = json_decode($gstin_decrypt_data, true);
		// dd($decoded_gstin_output);
		// dd($decoded_gstin_output);
		// $aes_final_decoded_plain_text = explode(',', $gstin_decrypt_data);
		// dump($aes_final_decoded_plain_text);

		// $remove_open = str_replace("{", "", $aes_final_decoded_plain_text);
		// dump($remove_open);
		// $remove_close = str_replace("}", "", $remove_open);
		// dump($remove_close);
		// $gst_validate = [];
		// foreach ($decoded_gstin_output as $key => $val) {
		// 	dd($val);
		// 	if ($val == 'irnStatus=0') {
		// 		DB::beginTransaction();

		// 		$api_log = new ApiLog;
		// 		$api_log->type_id = 24; //CUSTOMER
		// 		$api_log->entity_number = $gstin;
		// 		$api_log->entity_id = NULL;
		// 		$api_log->url = $bdo_check_gstin_url;
		// 		$api_log->src_data = $params;
		// 		// $api_log->response_data = json_encode($aes_final_decoded_plain_text);
		// 		$api_log->response_data = json_encode($gstin_decrypt_data);
		// 		$api_log->user_id = Auth::user()->id;
		// 		$api_log->status_id = 11272; //FAILED //SUCCESS
		// 		$api_log->errors = 'Something went Wrong!';
		// 		$api_log->created_by_id = Auth::user()->id;
		// 		$api_log->save();

		// 		DB::commit();

		// 		return response()->json([
		// 			'success' => false,
		// 			'error' => 'Something went Wrong!',
		// 		]);
		// 	} elseif ($val == 'Status=0') {
		// 		DB::beginTransaction();

		// 		$api_log = new ApiLog;
		// 		$api_log->type_id = 24; //CUSTOMER
		// 		$api_log->entity_number = $gstin;
		// 		$api_log->entity_id = NULL;
		// 		$api_log->url = $bdo_check_gstin_url;
		// 		$api_log->src_data = $params;
		// 		$api_log->response_data = json_encode($gstin_decrypt_data);
		// 		// $api_log->response_data = json_encode($aes_final_decoded_plain_text);
		// 		$api_log->user_id = Auth::user()->id;
		// 		$api_log->status_id = 11272; //FAILED //SUCCESS
		// 		$api_log->errors = 'Invalid GSTIN for this user';
		// 		$api_log->created_by_id = Auth::user()->id;
		// 		$api_log->save();

		// 		DB::commit();

		// 		return response()->json([
		// 			'success' => false,
		// 			'error' => 'Invalid GSTIN for this user!',
		// 		]);
		// 	} else {
		// 		DB::beginTransaction();

		// 		$api_log = new ApiLog;
		// 		$api_log->type_id = 24; //CUSTOMER
		// 		$api_log->entity_number = $gstin;
		// 		$api_log->entity_id = NULL;
		// 		$api_log->url = $bdo_check_gstin_url;
		// 		$api_log->src_data = $params;
		// 		$api_log->response_data = json_encode($gstin_decrypt_data);
		// 		// $api_log->response_data = json_encode($aes_final_decoded_plain_text);
		// 		$api_log->user_id = Auth::user()->id;
		// 		$api_log->status_id = 11272; //FAILED //SUCCESS
		// 		$api_log->errors = '';
		// 		$api_log->created_by_id = Auth::user()->id;
		// 		$api_log->save();

		// 		DB::commit();

		// 		$gst_validate[$key] = $val;
		// 	}
		// }
		// dd($gst_validate);
		if (key($gst_validate) == 'irnStatus') {
			if ($gst_validate['irnStatus'] == 0) {
				DB::beginTransaction();

				$api_log = new ApiLog;
				$api_log->type_id = 24; //CUSTOMER
				$api_log->entity_number = $gstin;
				$api_log->entity_id = NULL;
				$api_log->url = $bdo_check_gstin_url;
				$api_log->src_data = $params;
				// $api_log->response_data = json_encode($aes_final_decoded_plain_text);
				$api_log->response_data = json_encode($gstin_decrypt_data);
				$api_log->user_id = Auth::user()->id;
				$api_log->status_id = 11272; //FAILED //SUCCESS
				$api_log->errors = 'Something went Wrong!';
				$api_log->created_by_id = Auth::user()->id;
				$api_log->save();

				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Something went Wrong!',
				]);
			}
		} elseif (key($gst_validate) == 'Status') {
			if ($gst_validate['irnStatus'] == 0) {

				DB::beginTransaction();

				$api_log = new ApiLog;
				$api_log->type_id = 24; //CUSTOMER
				$api_log->entity_number = $gstin;
				$api_log->entity_id = NULL;
				$api_log->url = $bdo_check_gstin_url;
				$api_log->src_data = $params;
				$api_log->response_data = json_encode($gstin_decrypt_data);
				// $api_log->response_data = json_encode($aes_final_decoded_plain_text);
				$api_log->user_id = Auth::user()->id;
				$api_log->status_id = 11272; //FAILED //SUCCESS
				$api_log->errors = 'Invalid GSTIN for this user';
				$api_log->created_by_id = Auth::user()->id;
				$api_log->save();

				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Invalid GSTIN for this user!',
				]);
			}
		} else {
			DB::beginTransaction();

			$api_log = new ApiLog;
			$api_log->type_id = 24; //CUSTOMER
			$api_log->entity_number = $gstin;
			$api_log->entity_id = NULL;
			$api_log->url = $bdo_check_gstin_url;
			$api_log->src_data = $params;
			$api_log->response_data = json_encode($gstin_decrypt_data);
			// $api_log->response_data = json_encode($aes_final_decoded_plain_text);
			$api_log->user_id = Auth::user()->id;
			$api_log->status_id = 11271; //FAILED //SUCCESS
			$api_log->errors = '';
			$api_log->created_by_id = Auth::user()->id;
			$api_log->save();

			DB::commit();
		}

		$legal_name = NULL;
		$trade_name = NULL;
		$error = NULL;
		$address = '';
		$date_of_registration = null;
		// dump($gst_validate);
		// dd(1);
		// if ($gst_validate) {
		// 	foreach ($gst_validate as $value) {
		// 		//TRADE NAME
		// 		if (!empty($value)) {
		// 			//TRADE NAME
		// 			if ((strpos($value, "TradeName=") !== false)) {
		// 				$trade_name = explode("=", $value);
		// 				if ($trade_name[0] == 'TradeName') {
		// 					$trade_name = $trade_name[1];
		// 				} else {
		// 					$trade_name = NULL;
		// 				}
		// 			}
		// 			// LEGAL NAME
		// 			if ((strpos($value, " LegalName=") !== false)) {
		// 				$legal_name = explode("=", $value);
		// 				if ($legal_name[0] == ' LegalName') {
		// 					$legal_name = $legal_name[1];
		// 				} else {
		// 					$legal_name = NULL;
		// 				}
		// 			}
		// 			//ERROR MSG
		// 			if ((strpos($value, " ErrorMsg=") !== false)) {
		// 				$error = explode("=", $value);
		// 				if ($error[0] == ' ErrorMsg') {
		// 					$error = $error[1];
		// 				} else {
		// 					$error = NULL;
		// 				}
		// 			}

		// 		}
		// 	}
		// } else {
		// 	return response()->json([
		// 		'success' => false,
		// 		'error' => 'Server went wrong. Try again later!',
		// 	]);
		// }
		if ($gst_validate) {
			// dump('in');
			//ERROR
			// dump($gst_validate);
			// dump(key($gst_validate));
			if (key($gst_validate) == 'ErrorCodes') {
				if ($gst_validate['ErrorMsg']) {
					$error = $gst_validate['ErrorMsg'];
				} else {
					$error = NULL;
				}
			} else {
				//TRADE NAME
				if ($gst_validate['TradeName']) {
					$trade_name = $gst_validate['TradeName'];
				} else {
					$trade_name = NULL;
				}
				// LEGAL NAME
				if ($gst_validate['LegalName']) {
					$legal_name = $gst_validate['LegalName'];
				} else {
					$legal_name = NULL;
				}

				if (isset($gst_validate['AddrBno'])) {
                    $address .= $gst_validate['AddrBno'];
                    $address .= ',';
                }
                if (isset($gst_validate['AddrSt'])) {
                    $address .= $gst_validate['AddrSt'];
                    $address .= ',';
                }
                if (isset($gst_validate['AddrLoc'])) {
                    $address .= $gst_validate['AddrLoc'];
                    $address .= ',';
                }

                if (isset($gst_validate['StateCode'])) {
                    $state = State::where('e_invoice_state_code', $gst_validate['StateCode'])
                    	->pluck('name')
                    	->first();
                    if ($state) {
                        $address .= $state;
                        $address .= ',';
                    }
                }

                if (isset($gst_validate['AddrPncd'])) {
                    $address .= $gst_validate['AddrPncd'];
                    $address .= '';
                }

                if(isset($gst_validate['DtReg'])){
                	$date_of_registration = $gst_validate['DtReg'];
                }
			}
		} else {
			return response()->json([
				'success' => false,
				'error' => 'Server went wrong. Try again later!',
			]);
		}

		// dd($trade_name, $legal_name, $error);
		if ($legal_name || $trade_name) {
			return response()->json([
				'success' => true,
				'trade_name' => $trade_name,
				'legal_name' => $legal_name,
				'address' => $address,
				'date_of_registration' => $date_of_registration,
			]);
		} else {
			return response()->json([
				'success' => false,
				'error' => $error,
			]);
		}
	}

	public static function decryptAesData($app_secret_key, $bdo_sek_data) {
		$method = 'aes-256-ecb';

		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

		$decrypted = openssl_decrypt(base64_decode($bdo_sek_data), $method, $app_secret_key, OPENSSL_RAW_DATA, $iv);
		return $decrypted;
	}
}
