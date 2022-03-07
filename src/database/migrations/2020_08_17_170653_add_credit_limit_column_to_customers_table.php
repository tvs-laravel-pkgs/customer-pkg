<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCreditLimitColumnToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
          $table->unsignedDecimal('credit_limits',12,2)->nullable()->after('binary_gender_id')->comment('Used in VIMS Sale Request');
          $table->unsignedInteger('credit_days')->nullable()->after('credit_limits')->comment('Used in VIMS Sale Request');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
          $table->dropColumn('credit_limits');
          $table->dropColumn('credit_days');
        });
    }
}
