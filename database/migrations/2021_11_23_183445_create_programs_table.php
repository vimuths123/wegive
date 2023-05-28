<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('organization_id');
            $table->text('description')->nullable();
            $table->bigInteger('revenue')->default(0);
            $table->bigInteger('grant_expense')->default(0);
            $table->bigInteger('total_expense')->default(0);
            $table->date('e_return_period')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('programs');
    }
}
