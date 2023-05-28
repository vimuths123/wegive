<?php

use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('correlation_id')->nullable()->index();
            $table->foreignId('user_id')->constrained(); // transactions created by user
            $table->string('description');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('fee')->default(0);
            $table->integer('type')->default(Transaction::TYPE_ONCE);
            $table->integer('status')->default(Transaction::STATUS_PENDING);
            $table->morphs('source');
            $table->morphs('owner');
            $table->morphs('destination');
            $table->foreignId('givelist_id')->nullable()->constrained();
            $table->foreignId('fund_id')->nullable()->constrained();
            $table->foreignId('fundraiser_id')->nullable()->constrained();
            $table->foreignId('scheduled_donation_id')->nullable()->constrained();
            $table->foreignId('payout_id')->nullable()->constrained();
            $table->string('pf_id')->nullable()->index();
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
        Schema::dropIfExists('transactions');
    }
}
