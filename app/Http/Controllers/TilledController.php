<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TilledController extends Controller
{
    public function paymentSucceeded(Request $request)
    {
        $body = $request->json();

        $transaction = Transaction::where('correlation_id', $body['data']['id']);

        if (!$transaction) abort(404, 'Payment Intent Not Found');

        $transaction->status = Transaction::STATUS_SUCCESS;
        
        $transaction->save();

        Mail::raw($request->json(), function ($message) {
            $message->to('charlie@givelistapp.com');
            $message->subject('Payment Success');
        });
        
    }


    public function paymentFailed(Request $request)
    {
        $body = $request->json();

        $transaction = Transaction::where('correlation_id', $body['data']['id']);

        if (!$transaction) abort(404, 'Payment Intent Not Found');

        $transaction->status = Transaction::STATUS_FAILED;
        
        $transaction->save();

        Mail::raw($request->json(), function ($message) {
            $message->to('charlie@givelistapp.com');
            $message->subject('Payment Failed');
        });
        
    }
}
