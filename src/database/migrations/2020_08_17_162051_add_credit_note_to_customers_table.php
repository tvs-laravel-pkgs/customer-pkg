<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCreditNoteToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedDecimal('credit_note',12,2)->nullable()->after('binary_gender_id')->comment('Used in Vims Sale Requests');
            $table->unsignedInteger('credit_days')->nullable()->after('credit_note')->comment('Used in Vims Sale Requests');
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
            $table->dropColumn('credit_note');
            $table->dropColumn('credit_days');
        });
    }
}
