<?php

namespace App\Console\Commands;

use App\Models\Donor;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLglDonationData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:import-lgl-donations {organization_id}';

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
        $i = 0;

        $transactions = [];
        foreach ($rows as $rawRow) {

            if ($i <= 0) {
                $i++;

                continue;
            }


            $row = array_combine($keys, $rawRow);

            $donorProfile = Donor::where('lgl_id', $row['lgl_constituent_id'])->where('organization_id', $this->argument('organization_id'))->get()->first();

            if (!$donorProfile) {
                dump('no donor found', $row);
                continue;
            }


            $transactions[] = ['description' => $row['constituent_name'], 'amount' => ((float) $row['gift_amount']) * 100, 'fee' => 0, 'status' => 2, 'source_type' => 'organization', 'source_id' => $this->argument('organization_id'), 'owner_type' => $donorProfile->getMorphClass(), 'owner_id' => $donorProfile->id, 'destination_type' => 'organization', 'destination_id' => $this->argument('organization_id'), 'direct_deposit' => 1, 'anonymous' => 1, 'lgl_id' => null, 'created_at' => new DateTime($row['gift_date'])];

            $i++;
        }

        DB::table('transactions')->insert($transactions);






        return 0;
    }


    private function fileHandle()
    {
        return fopen(storage_path('lgldonationimport.csv'), 'r');
    }

    private function lineCount()
    {
        $count = 0;
        $handle = $this->fileHandle();
        while (!feof($handle)) {
            fgets($handle);
            $count++;
        }

        fclose($handle);
        return $count;
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
        'LGl Constituent ID' => 'lgl_constituent_id',
        'Constituent Name' => 'constituent_name',
        'LGL Gift ID' => 'lgl_gift_id',
        'External Gift ID' => 'external_gift_id',
        'LGL Parent Gift ID' => 'lgl_parent_gift_id',
        'Gift Type' => 'gift_type',
        'Campaign' => 'campaign',
        'LGL Campaign ID' => 'lgl_campaign_id',
        'Fund' => 'fund',
        'LGL Fund ID' => 'lgl_fund_id',
        'Appeal' => 'appeal',
        'LGL Appeal ID' => 'lgl_appeal_id',
        'Event' => 'event',
        'LGL Event ID' => 'lgl_event_id',
        'Gift Category' => 'gift_category',
        'LGL Gift Category ID' => 'lgl_gift_category_id',
        'Gift note' => 'gift_note',
        'Gift Amount' => 'gift_amount',
        'Deductible amount' => 'deductible_amount',
        'Deposited amount' => 'deposited_amount',
        'Gift date' => 'gift_date',
        'Deposit Date' => 'deposit_date',
        'Payment type' => 'payment_type',
        'Check/Reference No.' => 'check_number',
        'Pledge payment?' => 'pledge',
        'Anonymous gift?' => 'anonymous',
        'Gift owner' => 'gift_owner',
        'Gift batch ID' => 'gift_batch_id',
        'Created date' => 'created_at',

    ];
}
