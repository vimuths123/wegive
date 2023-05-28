<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomEmailDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_email_domains', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('organization_id');
            $table->string('domain');
        });

        Schema::table('message_templates', function (Blueprint $table) {
            $table->foreignId('custom_email_domain_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_email_domains');

        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn('custom_email_domain_id');
        });
    }
}
