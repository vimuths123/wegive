<?php

use App\Models\CustomQuestion;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_questions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('title');
            $table->integer('input_type')->default(CustomQuestion::INPUT_TYPE_TEXT);
            $table->integer('save_type')->default(CustomQuestion::CHECKOUT_ANSWER);
            $table->integer('order')->nullable();
            $table->foreignId('checkout_id');
            $table->boolean('required')->default(true);
            $table->json('answer_options')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_questions');
    }
}
