<?php

namespace Abs\CustomerPkg;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDimension extends Model {
	use SoftDeletes;
	protected $table = 'customer_dimensions';
	public $timestamps = true;
	protected $fillable = [
		'customer_id',
		'company_id',
	];
}
