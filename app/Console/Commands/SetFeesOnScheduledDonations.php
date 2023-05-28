<?php

namespace App\Console\Commands;

use App\Models\Bank;
use App\Models\Card;
use Illuminate\Console\Command;
use App\Models\ScheduledDonation;

class SetFeesOnScheduledDonations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:set-fees-on-scheduled-donations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $scheduledDonations = ScheduledDonation::where('destination_type', 'organization')->where('amount', '>', 0)->where('fee_amount', 0)->get();

        foreach ($scheduledDonations as $s) {
            $productCodes = $s->destination->productCodes();

            $pm = $s->paymentMethod;

            $productCode = null;

            if ($pm instanceof Card) {
                $productCode = $productCodes->where('type', 'Card')->get()->first();
            }
            if ($pm instanceof Bank) {
                $productCode = $productCodes->where('type', 'ACH')->get()->first();
            }

            if (!$productCode) {
                dump($s);
                continue;
            };

            $fee = $this->calculateFee($s->amount / 100, $pm, $productCode);
            $s->fee_amount = $fee * 100;
            $s->saveQuietly();
        }
    }



    public function calculateFee($amount, $pm, $pc)
    {

        $type = $pm->issuer;

        $productCodeMap = [
            1 => function ($amount, $type) {
                return 1.25;
            },
            2 => function ($amount, $type) {
                return 1;
            },
            3 => function ($amount, $type) {
                return .5;
            },
            4 => function ($amount, $type) {
                return (($amount) / (1 - .008)) - $amount;
            },
            5 => function ($amount, $type) {
                return (($amount + .5) / (1 - .005)) - $amount;
            },
            6 => function ($amount, $type) {
                return .25;
            },
            7 => function ($amount, $type) {
                if ($type === 'amex') {
                    return (($amount + .3) / (1 - .035)) - $amount;
                }
                return (($amount + .3) / (1 - .029)) - $amount;
            },
            8 => function ($amount, $type) {
                if ($type === 'amex') {
                    return (($amount + .3) / (1 - .029)) - $amount;
                }
                return (($amount + .3) / (1 - .029)) - $amount;
            },
            9 => function ($amount, $type) {
                if ($type === 'amex') {
                    return (($amount + .3) / (1 - .029)) - $amount;
                }
                return (($amount + .3) / (1 - .027)) - $amount;
            },

            10 => function ($amount, $type) {
                if ($type === 'amex') {
                    return (($amount + .3) / (1 - .027)) - $amount;
                }
                return (($amount + .3) / (1 - .025)) - $amount;
            },

            10 => function ($amount, $type) {
                if ($type === 'amex') {
                    return (($amount + .3) / (1 - .032)) - $amount;
                }
                return (($amount + .3) / (1 - .025)) - $amount;
            },

        ];

        $fee = $productCodeMap[$pc->id]($amount, $type);

        return $fee;
    }
}
