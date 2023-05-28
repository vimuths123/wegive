<?php

use App\Models\Checkout;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddElementFieldsToCheckouts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->boolean('allow_one_time')->default(true);
            $table->boolean('allow_recurring')->default(true);
            $table->boolean('total_limit')->default(false);
            $table->integer('total_limit_count')->nullable();
            $table->boolean('donor_limit')->default(false);
            $table->integer('donor_limit_count')->nullable();
            $table->boolean('preset_amount_type')->default(Checkout::PRESET_AMOUNT_CUSTOM_AND_SUGGESTED);
            $table->integer('preset_amount')->nullable();
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
            $table->dropColumn(['allow_one_time',
            'allow_recurring',
            'total_limit',
            'total_limit_count',
            'donor_limit',
            'donor_limit_count',
            'preset_amount_type',
            'preset_amount']);
        });
    }
}
