<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->morphs('owner');
            $table->integer('trigger');
            $table->text('content');
            $table->boolean('enabled');
            $table->integer('type');
        });

        Schema::dropIfExists('emails');
        Schema::dropIfExists('sms');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_templates');
    }
}
