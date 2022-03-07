<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDobInCustomer extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasColumn('customers', 'dob')) {
			Schema::table('customers', function (Blueprint $table) {
				$table->date('dob')->nullable()->after('customer_status')->comment('Used in VIMS');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		if (Schema::hasColumn('customers', 'dob')) {
			Schema::table('customers', function (Blueprint $table) {
				$table->dropColumn('dob');
			});
		}
	}
}
