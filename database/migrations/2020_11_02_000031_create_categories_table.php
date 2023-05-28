<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCategoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('categories', function(Blueprint $table)
		{
			$table->id();
			$table->string('name')->index();
            $table->string('slug')->nullable();
            $table->string('ntees');
            $table->text('description')->nullable();
			$table->string('color')->default('#28AAE2');
            $table->tinyInteger('tier');
            $table->json('metadata')->nullable();
			$table->foreignId('parent_id')->nullable()->constrained('categories');
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
		Schema::drop('categories');
	}

}
