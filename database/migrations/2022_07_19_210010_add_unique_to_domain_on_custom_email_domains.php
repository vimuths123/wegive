<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueToDomainOnCustomEmailDomains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_email_domains', function (Blueprint $table) {
            $table->unique('domain', 'custom_email_domains_domain_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_email_domains', function (Blueprint $table) {
            $table->dropUnique('custom_email_domains_domain_unique');
        });
    }
}
