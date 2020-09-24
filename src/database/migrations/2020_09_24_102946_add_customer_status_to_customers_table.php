<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerStatusToCustomersTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('customers', function (Blueprint $table) {
			if (!Schema::hasColumn('customers', 'customer_status')) {
				$table->unsignedInteger('customer_status')->nullable()->after('credit_days')->comment('Used in BPAS Receipt');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('customers', function (Blueprint $table) {
			if (Schema::hasColumn('customers', 'customer_status')) {
				$table->dropColumn('customer_status');
			}
		});
	}
}
