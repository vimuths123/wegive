<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('handle')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('active')->default(1);

            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('phone')->nullable();


            $table->nullableMorphs('preferred_payment');

            $table->unsignedInteger('scheduled_donation_amount')->nullable()->default(null);
            $table->unsignedInteger('scheduled_donation_frequency')->nullable()->default(null);
            $table->date('next_scheduled_donation_at')->nullable()->index();
            $table->string('type')->nullable();
            $table->timestamp('is_public')->nullable();

            $table->string('uuid')->nullable();

           

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
