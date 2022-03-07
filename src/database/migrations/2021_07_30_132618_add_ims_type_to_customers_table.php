<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImsTypeToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers','ims_type_id')) {
                $table->unsignedInteger('ims_type_id')->after('pdf_format_id')->comment('Used in vims parts request')->nullable();
                $table->foreign("ims_type_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
            }
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
            if (Schema::hasColumn('customers','ims_type_id')) {
                $table->dropForeign('customers_ims_type_id_foreign');
                $table->dropColumn('ims_type_id');
            }
        });
    }
}
