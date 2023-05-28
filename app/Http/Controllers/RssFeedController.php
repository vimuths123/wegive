<?php

namespace App\Http\Controllers;

use App\Models\Givelist;
use App\Models\Transaction;
use Illuminate\Http\Request;

class RssFeedController extends Controller
{
    public function givelistFeed()
    {
        $transactionsOne = Transaction::whereIn('givelist_id', [72, 73, 75])->where('status', '!=',  Transaction::STATUS_FAILED)->get();

        $transactionsTwo = Transaction::whereIn('destination_id', [72, 73, 75])->where([['destination_type', 'givelist'], ['status', '!=',  Transaction::STATUS_FAILED]])->get();


        $transactions = $transactionsOne->merge($transactionsTwo);

        $count = count($transactions->unique('user_id'));

        $sum = number_format($transactions->unique('id')->sum('amount') / 100);

        return response()->view('rss.givelistfeed', ['amount' => "\${$sum}", 'donors' => $count])->header('Content-Type', 'application/xml');
    }
}
