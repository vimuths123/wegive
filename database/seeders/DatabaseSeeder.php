<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Checkout;
use App\Models\Donor;
use App\Models\DonorPortal;
use App\Models\Login;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $organizations = Organization::factory(10)->create();

        foreach ($organizations as &$organization) {
            $checkout = new Checkout();
            $organization->checkouts()->save($checkout);
            $donorPortal = new DonorPortal();
            $donorPortal->checkout()->associate($checkout);
            $organization->donorPortal()->save($donorPortal);
        }

        $users = User::factory(9)->create();
        $user = User::factory()->create(['first_name' => 'Charles', 'last_name' => 'Minderhout', 'email' => 'charleswminderhout@gmail.com', 'phone' => '8184382568']);
        $user = User::factory()->create(['first_name' => 'Jonathan', 'last_name' => 'Beck', 'email' => 'jonathanbeck@gmail.com', 'phone' => '9259848490']);

        $users = User::all();

        foreach ($users as $user) {
            $donor = Donor::factory()->make(['first_name' => $user->first_name, 'last_name' => $user->last_name]);

            foreach ($organizations as &$organization) {
                $donor = $donor->replicate();
                $donor->organization()->associate($organization)->save();
            }

            $login = new Login();
            $login->loginable()->associate($donor);
            $user->logins()->save($login);

            $user->currentLogin()->associate($donor);
            $user->save();
        }

        foreach ($users as $user) {
            $donor = $user->currentLogin;

            $card = Card::factory()->make();
            $card->owner()->associate($donor);
            $card->name = $donor->name;
            $card->save();

            foreach ($organizations as $organization) {
                $transactions = Transaction::factory(3)->create(['owner_id' => $donor->id, 'owner_type' => 'donor', 'source_id' => $card->id, 'source_type' => 'card', 'destination_type' => 'organization', 'destination_id' => $organization->id]);

                $login = new Login();
                $login->loginable()->associate($organization);
                $user->logins()->save($login);
            }
        }
    }
}
