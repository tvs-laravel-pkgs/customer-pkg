<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCashLimitStatusToCustomersTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('customers', function (Blueprint $table) {
			$table->boolean('cash_limit_status')->after('binary_gender_id')->default(0)->comment('0-Not Allowed, 1-Allowed used in BPAS Receipt validation');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('customers', function (Blueprint $table) {
			$table->dropColumn('cash_limit_status');
		});
	}
}
