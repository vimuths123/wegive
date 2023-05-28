<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomEmailAddressIdToMessageTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->foreignId('custom_email_address_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn('custom_email_address_id');
        });
    }
}
