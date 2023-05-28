<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndividualsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('individuals', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email_1')->nullable();
            $table->string('email_2')->nullable();
            $table->string('email_3')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('other_phone')->nullable();
            $table->timestamp('is_public')->nullable();
            $table->foreignId('donor_id');
            $table->foreignId('organization_id');
            $table->nullableMorphs('preferred_payment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('individuals');
    }
}
