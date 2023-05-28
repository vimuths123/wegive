<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGivelistsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('givelists', function(Blueprint $table)
		{
			$table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
			$table->boolean('active')->default(1);
			$table->boolean('is_public')->default(0);
			$table->timestamps();
			$table->softDeletes();

			$table->string('uuid')->nullable();

        });
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('givelists');
	}

}
