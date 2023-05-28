<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGivelistOrganizationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('givelist_organization', function(Blueprint $table)
		{
            $table->foreignId('givelist_id')->constrained();
			$table->foreignId('organization_id')->constrained();
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
		Schema::drop('givelist_organization');
	}

}
