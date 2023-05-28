<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['address1', 'address2', 'phone', 'handle', 'slug', 'city', 'state', 'zip', 'tl_token']);
            $table->string('email_1')->nullable();
            $table->string('email_2')->nullable();
            $table->string('email_3')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('other_phone')->nullable();
            $table->foreignId('donor_id');
            $table->foreignId('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
