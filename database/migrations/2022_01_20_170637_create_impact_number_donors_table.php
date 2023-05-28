<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImpactNumberDonorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('impact_number_donor', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('donor_id');
            $table->foreignId('impact_number_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('impact_number_donor');
    }
}
