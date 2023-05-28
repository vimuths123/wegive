<?php

use App\Models\Fundraiser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSlugToFundraisers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fundraisers', function (Blueprint $table) {
            $table->string('slug')->nullable();
        });

        $fundraisers = Fundraiser::all();
        foreach($fundraisers as $fundraiser) {
                $fundraiser->slug = Str::slug($fundraiser->name);
                $fundraiser->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fundraisers', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}
