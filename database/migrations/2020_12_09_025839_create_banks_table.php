<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->boolean('primary')->default(false);
            $table->timestamp('user_agreed')->nullable();
            $table->string('name')->nullable();
            $table->string('last_four')->nullable();
            $table->string('pf_token')->nullable();
            $table->string('pf_id')->nullable();
            $table->string('stripe_id')->nullable();
            $table->string('plaid_token')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('banks');
    }
}
