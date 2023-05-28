<?php

namespace App\Console\Commands;

use App\Models\Donor;
use App\Models\User;
use App\Processors\Tilled;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CreateChildHopeTilledCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:create-child-hope-tilled-customers {organization_id}';

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
        ini_set('memory_limit', '-1');

        if (!$this->argument('organization_id')) return 0;
        $rows = $this->import();
        $keys = array_values($this->map); // $keys = $rows->current();

        $fp = tmpfile();

        fputcsv($fp, ['stripe id', 'tilled id']);



        foreach ($rows as $rawRow) {

            $row = array_combine($keys, $rawRow);

            $donorProfile = Donor::where('lgl_id', $row['lgl_id'])->where('organization_id', $this->argument('organization_id'))->get()->first();

            if (!$donorProfile) {
                continue;
            }

            $user = User::where('email', $donorProfile->email_1)->first();

            $tilledToken = $user->tl_token;

            if (!$tilledToken) {
                $tilled = new Tilled();
                $customer = $tilled->createCustomer([
                    'email' => $user->email ?? null,
                    'first_name' => $user->first_name ?? null,
                    'last_name' => $user->last_name ?? null,
                    // 'metadata' => [], // not currently used
                    'phone' => $user->phone ?? null,
                ]);

                if ($customer->failed()) {
                    continue;
                }

                $user->tl_token = $customer['id'];
                $user->save();

                $tilledToken = $user->tl_token;
                dump($tilledToken);
            }

            fputcsv($fp, [$row['stripe_id'], $tilledToken]);
        }



        Mail::send('emails.brian', [], function ($message) use ($fp) {
            $message->to('charlie@givelistapp.com')
                ->subject('CSV');

            $message->attach(stream_get_meta_data($fp)['uri'], ['as' => 'DonorList.csv', 'mime' => 'text/csv']);
        });

        fclose($fp);


        return 0;
    }


    private function fileHandle()
    {
        return fopen(storage_path('childhopestripe.csv'), 'r');
    }


    private function import()
    {
        $file = $this->fileHandle();
        while (($line = fgetcsv($file)) !== false) {
            yield $line;
        }

        fclose($file);
    }

    private $map = [
        'LGL ID' => 'lgl_id',
        'Stripe Id' => 'stripe_id',
        'Description' => 'description',
        'LGL Last Name' => 'lgl_last_name',
        'Stripe Token' => 'stripe_token',
        'Email' => 'email',
        'Name' => 'name',
        'Created (UTC)' => 'created_at',
        'Delinquent' => 'delinquent',
        'Card ID' => 'card_id',
        'Card Last4' => 'last_four',
        'Card Brand' => 'issuer',
        'Card Funding' => 'card_type',
        'Card Exp Month' => 'expiration_month',
        'Card Exp Year' => 'expiration_year',
        'Card Name' => 'card_holder',
        'Card Address Line1' => 'address_1',
        'Card Address Line2' => 'address_2',
        'Card Address City' => 'city',
        'Card Address State' => 'state',
        'Card Address Country' => 'country',
        'Card Address Zip' => 'zip',
        'Card Issue Country' => 'issue_country',
        'Card Fingerprint' => 'card_fingerprint',
        'Card CVC Status' => 'cvc_status',
        'Card AVS Zip Status' => 'avs_zip_status',
        'Card AVS Line1 Status' => 'avs_line_status',
        'Card Tokenization Method' => 'tokenization_status',
        'Plan' => 'plan',
        'Status' => 'status',
        'Cancel At Period End' => 'cancel_at',
        'Account Balance' => 'account_balance',
        'Currency' => 'currency',
        'Total Spend' => 'total_spend',
        'Payment Count' => 'payment_count',
        'Average Order' => 'average_order',
        'Refunded Volume' => 'refunded_volume',
        'Dispute Losses' => 'dispute_losses',
        'Business Vat ID' => 'vat_id'
    ];
}
