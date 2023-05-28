<?php

namespace App\Jobs;

use Exception;
use League\Csv\Writer;
use SplTempFileObject;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ExportTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $organization = null;
    protected $email = null;
    protected $files = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Organization $organization, $email)
    {
        $this->organization = $organization;
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');
        try {
            $transactions = $this->organization->receivedTransactions();
            $keys = array_keys(json_decode(json_encode($transactions->first()), true));
            unset($keys[0]);
            unset($keys[1]);
            $keys[] = 'donor_name';
            $keys[] = "user_email";
            $keys[] = "user_phone";
            $email = $this->email;



            $this->organization->receivedTransactions()->chunk(10000, function ($chunkTrans) use ($keys) {
                $csv = Writer::createFromFileObject(new SplTempFileObject());

                $csv->insertOne($keys);

                foreach ($chunkTrans as $t) {
                    $tData = json_decode(json_encode($t), true);
                    unset($tData['id']);
                    unset($tData['correlation_id']);
                    $tData['name'] = $t->owner->name ?? 'Anonymous';
                    $tData['user_email'] = $t->user ? $t->user->email : null;
                    $tData['user_phone'] = $t->user ? $t->user->phone : null;
                    $tData['amount'] = $tData['amount'] / 100;
                    $csv->insertOne($tData);
                }

                $this->files[] = $csv->toString();
            });


            Mail::send('emails.brian', [], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Transaction List');

                foreach ($this->files as $key => $file) {
                    $page = $key + 1;
                    $message->attachData($file, "donation_export_page_{$page}.csv", [
                        'mime' => 'text/csv',
                    ]);
                }
            });
        } catch (Exception $e) {
            $this->fail($e);
        }
    }
}
