<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomerAddPdfFormat extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('customers', function (Blueprint $table) {
			if (!Schema::hasColumn('customers', 'pdf_format_id')) {
				$table->unsignedInteger('pdf_format_id')->nullable()->default(11310)->after('axapta_location_id')->comment('Used in CN/DN PDF');

				$table->foreign('pdf_format_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
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
			if (Schema::hasColumn('customers', 'pdf_format_id')) {
				$table->dropForeign('customers_pdf_format_id_foreign');

				$table->dropColumn('pdf_format_id');
			}
		});
	}
}
