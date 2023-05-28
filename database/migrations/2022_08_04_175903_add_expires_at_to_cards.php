<?php

use Carbon\Carbon;
use App\Models\Card;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpiresAtToCards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable();
        });


        $cards = Card::all();

        foreach ($cards as $c) {

            $dateArray = explode('/', $c->expiration);
            $d = new Carbon();
            $d->month($dateArray[0]);
            $d->year($dateArray[1]);
            $d = $d->endOfMonth();
            $c->expires_at = $d;
            $c->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
}
