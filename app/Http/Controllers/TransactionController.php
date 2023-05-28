<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Models\Organization;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TransactionController extends Controller
{
    public function tribute(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user->is(auth()->user()), 401, 'You do not have access to this resource');

        $transaction->tribute = true;
        $transaction->tribute_name = $request->tributeName;
        $transaction->tribute_message = $request->tributeMessage;
        $transaction->tribute_email = $request->tributeEmail;
        $transaction->save();

        if (!$request->tributeEmail) return;

        if ($transaction->destination instanceof Organization) {
            $messageTemplate = $transaction->destination->messageTemplates()->where('trigger', MessageTemplate::TRIGGER_TRIBUTE_MADE)->first();

            if ($messageTemplate) {
                $messageTemplate->send($transaction);
                return;
            }
        }

        Mail::send('emails.tribute', ['tributeName' => $request->tributeName, 'donorName' => $transaction->owner->name, 'destinationName' => $transaction->destination->name, 'tributeMessage' => $request->tributeMessage, 'logo' => $transaction->destination->getFirstMedia('avatar') ?  $transaction->destination->getFirstMedia('avatar')->getUrl() : null], function ($message) use ($request) {
            $message->to($request->tributeEmail)
                ->subject('Someone has donated in your honor');
        });
    }
}
