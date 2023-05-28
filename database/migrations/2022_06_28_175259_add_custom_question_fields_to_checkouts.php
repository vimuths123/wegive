<?php

use App\Models\Checkout;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomQuestionFieldsToCheckouts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->integer('place_questions')->default(Checkout::AFTER_CONFIRMATION);
            $table->boolean('ask_questions_again')->default(false);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropColumn(['place_questions',
            'ask_questions_again']);
        });
    }
}
