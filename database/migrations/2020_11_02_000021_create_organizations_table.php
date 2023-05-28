<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrganizationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('organizations', function(Blueprint $table)
		{
			$table->id();
            $table->string('name')->index();
			$table->string('ein')->nullable();
            $table->string('slug')->unique();

            $table->string('address')->nullable();
			$table->string('city')->nullable();
			$table->string('state')->nullable();
			$table->string('post_code')->nullable();
			$table->bigInteger('total_giving_amount')->nullable()->default(0);
			$table->text('tagline')->nullable();
			$table->string('operation')->nullable();
			$table->bigInteger('program_expense')->nullable()->default(0);
			$table->bigInteger('fundraising_expense')->nullable()->default(0);
			$table->boolean('active')->default(1);
			$table->string('phone')->nullable();
			$table->string('url')->nullable();
			$table->string('total_revenue')->nullable();
			$table->string('total_expenses')->nullable();
			$table->string('open990_id')->nullable();
			$table->string('irs_efile_id')->nullable();
			$table->date('tax_start_dt')->nullable();
			$table->date('tax_end_dt')->nullable();
			$table->string('logo_file')->nullable();
			$table->string('logo_url')->nullable();
			$table->text('keywords')->nullable();
			$table->string('year_of_formation')->nullable();
			$table->string('tier_2_tag')->nullable();
			$table->string('tag_2')->nullable();
			$table->string('tag_1')->nullable();
			$table->string('subsection')->nullable();
			$table->string('rating')->nullable();
			$table->string('organization_type')->nullable();
			$table->string('open990_url')->nullable();
			$table->string('ntee_description')->nullable();
			$table->string('country')->nullable();
			$table->bigInteger('general_expense')->nullable();
			$table->date('pub78_update_date')->nullable();
			$table->date('bmf_update_date')->nullable();
			$table->boolean('present_in_pub78')->nullable();
			$table->boolean('present_in_bmf')->nullable();
			$table->timestamp('onboarded')->nullable();
			$table->string('ntee')->nullable();
			$table->date('e_return_period')->nullable();
			$table->string('legal_name')->nullable();
			$table->string('dba')->nullable();
			$table->text('mission_statement')->nullable();
			$table->bigInteger('total_assets')->nullable();
			$table->bigInteger('total_liabilities')->nullable();
			$table->boolean('engaged_in_lobbying')->nullable();
			$table->boolean('grants_to_govt')->nullable();
			$table->boolean('has_controlled_entity')->nullable();
			$table->boolean('is_school')->nullable();
			$table->boolean('operates_hospital_facilities')->nullable();
			$table->boolean('transacted_with_controlled_entity')->nullable();
			$table->string('e_return_type')->nullable();
			$table->date('date_efile_submitted')->nullable();
			$table->date('date_efile_published')->nullable();
			$table->string('efile_schema_version')->nullable();
			$table->text('efile_xml')->nullable();
			$table->text('efile_pdf')->nullable();
            $table->json('manually_managed_fields')->nullable();
			$table->string('pf_id')->nullable()->index();

			$table->string('uuid')->nullable();


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
		Schema::drop('organizations');
	}

}
