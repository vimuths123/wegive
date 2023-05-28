<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('posts', function(Blueprint $table)
		{
			$table->id();
            $table->foreignId('user_id')->constrained();
			$table->foreignId('organization_id')->constrained();
			$table->mediumText('content');
			$table->string('youtube_link')->nullable();
			$table->timestamp('posted_at')->nullable();
			$table->timestamps();

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
		Schema::drop('posts');
	}

}
