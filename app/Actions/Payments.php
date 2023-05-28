<?php

namespace App\Actions;

use App\Models\Bank;
use App\Models\Card;
use App\Models\Givelist;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use App\Processors\Tilled;
use Illuminate\Support\Facades\Mail;

class Payments
{
    public static function processTransaction(Transaction $transaction): Transaction
    {

        if ($transaction->source instanceof Card || $transaction->source instanceof Bank) {

            $success = Transaction::where('source_type', $transaction->source_type)->where('source_id', $transaction->source_id)->where('status', Transaction::STATUS_SUCCESS)->get()->first();

            if ($success && $transaction->amount >= 10000000) {
                Mail::raw(json_encode($transaction), function ($message) {
                    $message->to(['Charlie@givelistapp.com'])
                        ->subject('An unusually large donation from an existing payment method has been made');
                });
                $transaction->status = Transaction::STATUS_PENDING;
                return $transaction;
            } else if (!$success && $transaction->amount >= 2500000) {
                Mail::raw(json_encode($transaction), function ($message) {
                    $message->to(['Charlie@givelistapp.com'])
                        ->subject('An unusually large donation from an unverified payment method has been made');
                });
                $transaction->status = Transaction::STATUS_PENDING;
                return $transaction;
            }
        }


        if ($transaction->destination instanceof Organization) {
            // Money sent from User or Company wallet
            if ($transaction->source instanceof User) {
                if ($transaction->source->clearedWalletBalance() < $transaction->amount) {
                    $transaction->status = Transaction::STATUS_FAILED;
                    return $transaction;
                }
            }

            return self::processToOrganization($transaction);
        }

        if ($transaction->destination instanceof Givelist) {
            // Money sent from User or Company wallet
            if ($transaction->source instanceof User) {
                if ($transaction->source->clearedWalletBalance() < $transaction->amount) {
                    $transaction->status = Transaction::STATUS_FAILED;
                    return $transaction;
                }
            }

            return self::processToGivelist($transaction);
        }

        if ($transaction->destination instanceof User) {

            if ($transaction->source instanceof User) {
                if ($transaction->source->clearedWalletBalance() < $transaction->amount) {
                    $transaction->status = Transaction::STATUS_FAILED;
                    return $transaction;
                }
            }

            return self::processToWallet($transaction);
        }

        return $transaction;
    }

    public static function processToWallet(Transaction $transaction): Transaction
    {
        $tilled = new Tilled();
        $response = null;

        $transaction->status = Transaction::STATUS_PENDING;


        if (empty($transaction->source->tl_token)) {
            $transaction->status = Transaction::STATUS_FAILED;


            return $transaction;
        }

        if ($transaction->source instanceof Card || $transaction->source instanceof Bank) {
            $response = $tilled->handleTilledPayment($transaction->source->tl_token, $transaction->amount, config('services.tilled.our_token'), [$transaction->id], $transaction->user);


            $details = $response->json();

            if ($response->successful()) {
                $transaction->correlation_id = $details['id'];
                if (($details['status'] ?? 'failed') === 'succeeded') {
                    $transaction->status = Transaction::STATUS_SUCCESS;
                }

                if ($response->json()['status'] === 'processing') {
                    $transaction->status = Transaction::STATUS_PROCESSING;
                }
            }

            if ($response->failed()) {
                $transaction->description = $details['message'] ?? 'Unknown payment related issue';

                $transaction->status = Transaction::STATUS_FAILED;

                return $transaction;
            }
        }

        return $transaction;
    }

    public static function processToOrganization(Transaction $transaction): Transaction
    {
        $tilled = new Tilled();
        $response = null;

        $transaction->status = Transaction::STATUS_PENDING;


        if ($transaction->source instanceof Card || $transaction->source instanceof Bank) {

            $destinationToken = $transaction->direct_deposit ? $transaction->destination->tl_token : config('services.tilled.our_token');

            $response = $tilled->handleTilledPayment($transaction->source->tl_token, $transaction->amount, $destinationToken, [$transaction->id], $transaction->user, $transaction->fee);

            $details = $response->json();



            if ($response->successful()) {
                $transaction->correlation_id = $details['id'];

                if ($details['status'] === 'succeeded') {
                    $transaction->status = Transaction::STATUS_SUCCESS;
                }

                if ($details['status'] === 'processing') {
                    $transaction->status = Transaction::STATUS_PROCESSING;
                }
            }

            if ($response->failed()) {
                $transaction->description = $details['message'] ?? 'Unknown payment related issue';

                $transaction->status = Transaction::STATUS_FAILED;

                return $transaction;
            }
        }



        if (($transaction->source instanceof User) && $transaction->direct_deposit) {

            $response = $tilled->handleTilledPayment(config('services.tilled.givelist_bank'), $transaction->amount, $transaction->destination->tl_token, [$transaction->id], $transaction->user, $transaction->fee);

            $details = $response->json();

            if ($response->successful()) {
                $transaction->correlation_id = $details['id'];

                if ($details['status'] === 'succeeded') {
                    $transaction->status = Transaction::STATUS_SUCCESS;
                }

                if ($details['status'] === 'processing') {
                    $transaction->status = Transaction::STATUS_PROCESSING;
                }
            }

            if ($response->failed()) {
                $transaction->description = $details['message'] ?? 'Unknown payment related issue';

                $transaction->status = Transaction::STATUS_FAILED;

                return $transaction;
            }
        } else if ($transaction->source instanceof User) {
            $transaction->status = Transaction::STATUS_SUCCESS;
        }


        return $transaction;
    }


    public static function processToGivelist(Transaction $transaction): Transaction
    {
        $tilled = new Tilled();
        $response = null;

        $transaction->status = Transaction::STATUS_PENDING;


        if ($transaction->source instanceof Card || $transaction->source instanceof Bank) {

            $destinationToken =  config('services.tilled.our_token');

            $response = $tilled->handleTilledPayment($transaction->source->tl_token, $transaction->amount, $destinationToken, [$transaction->id], $transaction->user, $transaction->fee);

            $details = $response->json();

            if ($response->successful()) {
                $transaction->correlation_id = $details['id'];

                if ($details['status'] === 'succeeded') {
                    $transaction->status = Transaction::STATUS_SUCCESS;
                }

                if ($details['status'] === 'processing') {
                    $transaction->status = Transaction::STATUS_PROCESSING;
                }
            }

            if ($response->failed()) {
                $transaction->description = $details['message'] ?? 'Unknown payment related issue';

                $transaction->status = Transaction::STATUS_FAILED;

                return $transaction;
            }
        }



        if (($transaction->source instanceof user)) {
            $transaction->status = Transaction::STATUS_SUCCESS;
        }


        return $transaction;
    }


    public static function processLumpSum($amount, $orgToken, $transactionIds)
    {
        $tilled = new Tilled();
        $response = null;
        $response = $tilled->createPaymentIntent(config('services.tilled.givelist_bank'), $amount, $orgToken, $transactionIds, 0);


        return $response;
    }
}
