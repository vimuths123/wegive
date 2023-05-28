<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnabledToDonorProfile extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('individuals', function (Blueprint $table) {
            $table->boolean('enabled')->default(true);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('individuals', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });
    }
}
