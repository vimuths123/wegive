<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVGSFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->string('vgs_number_token')->nullable();
            $table->string('vgs_security_code_token')->nullable();
            $table->string('zip_code')->nullable();
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->string('vgs_routing_number_token')->nullable();
            $table->string('vgs_account_number_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn([
                'vgs_number_token', 'vgs_security_code_token',
                'zip_code'
            ]);
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->string(['vgs_routing_number_token', 'vgs_account_number_token'])->nullable();
        });
    }
}
