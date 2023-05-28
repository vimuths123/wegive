<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Card;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckForExpiredCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:check-for-expired-cards {timeframe}';

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

        // if ($this->argument('timeframe') === 'expiring') {
        //     $cards = Card::whereMonth('expires_at', Carbon::now()->month)->whereYear('expires_at', Carbon::now()->year)->get();

        //     foreach ($cards as $c) {
        //         $this->sendUpdateEmail($c, "Your card ending in {$c->last_four} is expiring at the end of this month", "Please go into your donor portal to update this card.");
        //     }
        // } else {
        //     $cards = Card::whereDate('expires_at', today())->get();

        //     foreach ($cards as $c) {
        //         $this->sendUpdateEmail($c, "Your card ending in {$c->last_four} expires today", "Please go into your donor portal to update this card.");
        //     }
        // }

        return 0;
    }

    public function sendUpdateEmail($card, $subject, $body)
    {
        // $user = $card->owner;
        // Mail::send('emails.plaintext', ["body" => $body], function ($message) use ($user, $subject) {
        //     $message->to($user->email)
        //         ->subject($subject);
        // });
    }
}
