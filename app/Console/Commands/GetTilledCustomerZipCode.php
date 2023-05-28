<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GetTilledCustomerZipCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:get-tilled-customer-zip-code {organization_id}';

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

        fputcsv($fp, ['stripe id', 'tilled id', 'zip']);



        foreach ($rows as $rawRow) {

            $row = array_combine($keys, $rawRow);

            $user = User::where('tl_token', $row['tilled_id'])->first();

            if (!$user) {
                dump($rawRow);
                continue;
            };

            $login = $user->logins()->where('loginable_type', 'donor')->first();

            if (!$login) continue;

            $donor = $login->loginable;

            $donorProfile = $donor->donorProfiles()->where('organization_id', $this->argument('organization_id'))->first();

            if (!$donorProfile) continue;

            $address = $donorProfile->addresses()->first();

            if (!$address) return;

            $zip = $address->zip;



            fputcsv($fp, [$row['stripe_id'], $row['tilled_id'], $zip]);
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
        return fopen(storage_path('stripetilled.csv'), 'r');
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
        'stripe id' => 'stripe_id',
        'tilled id' => 'tilled_id',

    ];
}
