<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('companies', function (Blueprint $table) {
            $table->nullableMorphs('preferred_payment');
            $table->timestamp('is_public')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('phone')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('ein')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('preferred_payment');
            $table->dropColumn('is_public');
            $table->dropColumn('last_login_at');
            $table->dropColumn('phone');
            $table->dropColumn('address1');
            $table->dropColumn('address2');
            $table->dropColumn('city');
            $table->dropColumn('state');
            $table->dropColumn('zip');
            $table->dropColumn('ein');
        });
    }
}
