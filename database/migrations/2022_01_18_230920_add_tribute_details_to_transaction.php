<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTributeDetailsToTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('tribute_email')->nullable();
            $table->string('tribute_name')->nullable();
            $table->text('tribute_message')->nullable();
            $table->boolean('tribute')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'tribute_email',
                'tribute_message',
                'tribute',
                'tribute_name'
            ]);
        });
    }
}
