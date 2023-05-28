<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Donor;
use App\Models\Login;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportLglData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:import-lgl {organization_id}';

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
        die('Not Used Anymore');

        ini_set('memory_limit', '-1');

        if (!$this->argument('organization_id')) return 0;
        $rows = $this->import();
        $keys = array_values($this->map); // $keys = $rows->current();
        $i = 0;


        foreach ($rows as $rawRow) {

            if ($i <= 0) {
                $i++;

                continue;
            }

            $row = array_combine($keys, $rawRow);

            $emails = array_filter([$row['email_1'], $row['email_2'], $row['email_3']]);

            if ($row['type'] === 'Individual') {

                $individuals = Individual::whereIn('email_1', $emails)->orWhereIn('email_2', $emails)->orWhereIn('email_3', $emails)->get();

                $donors = $individuals->map(function ($row) {
                    return $row->donor;
                })->unique();


                if (count($donors) === 1) {
                    $this->updateIndividualAccount($emails, $donors->first(), $row);
                } else if (!count($donors)) {
                    $this->createIndividualAccount($emails, $row, false);
                } else if(count($donors) > 1) {
                    dump( 'Multiple donors with donor profiles that have matching emails, merge conflict.');
                }
            } else if( $row['type'] === 'Organization') {
                $companies = Company::whereIn('email_1', $emails)->orWhereIn('email_2', $emails)->orWhereIn('email_3', $emails)->get();

                $donors = $companies->map(function ($row) {
                    return $row->donor;
                })->unique();


                if (count($donors) === 1) {
                    $this->updateCompanyAccount($emails, $donors->first(), $row);
                } else if (!count($donors)) {
                    $this->createCompanyAccount($emails, $row, false);
                } else if(count($donors) > 1) {
                    dump( 'Multiple donors with donor profiles that have matching emails, merge conflict.');
                }

            }

            $i++;
        }

        return 0;
    }

    private function createIndividualAccount($emails, $data, $flag)
    {


        if (!count($emails)) return;

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $users[] = $user;
                continue;
            }

            $userData = ['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $email, 'phone' => $data['phone_1'], 'password' => Str::random()];

            try {
                $users[] = User::create($userData);
            } catch (Exception $e) {
            }
        }


        if (!count($users)) {
            dump('no users to relate');
            return;
        }

        $donor = new Donor();

        $donor->type = 'individual';

        $handle = $data['first_name'] . $data['last_name'];
        $handle = str_replace(' ', '', $handle);

        $existingDonor = null;

        if ($handle) $existingDonor = Donor::where('handle', $handle)->first();

        if ($existingDonor) {
            $randomNumber = rand(1, 10000);
            $donor->handle = "{$handle}{$randomNumber}";
        } else {
            $donor->handle = "{$handle}";
        }

        $donor->save();

        $individual = new Individual();
        $individual->first_name = $data['first_name'];
        $individual->last_name = $data['last_name'];
        $individual->email_1 = $data['email_1'];
        $individual->mobile_phone = $data['phone_1'];
        $individual->donor_id = $donor->id;


        $individual->organization_id = env('WEGIVE_DONOR_PROFILE');
        $individual->save();

        $address = new Address();
        $address->address_1 = $data['address_1'];
        $address->city = $data['city'];
        $address->state = $data['state'];
        $address->zip = $data['zip'];
        $address->type = 'mailing';
        $address->primary = true;
        $address->addressable()->associate($individual);
        $address->save();

        $address = new Address();
        $address->address_1 = $data['address_1'];
        $address->city = $data['city'];
        $address->state = $data['state'];
        $address->zip = $data['zip'];
        $address->type = 'billing';
        $address->primary = true;
        $address->addressable()->associate($individual);
        $address->save();

        $orgId = $this->argument('organization_id');


        if ($orgId !== "null" && $orgId && $orgId !== 'undefined') {
            $individual = new Individual();
            $individual->first_name = $data['first_name'];
            $individual->last_name = $data['last_name'];
            $individual->email_1 = $data['email_1'];
            $individual->mobile_phone = $data['phone_1'];
            $individual->donor_id = $donor->id;
            $individual->organization_id = $orgId;
            $individual->lgl_id = $data['lgl_id'];
            $individual->save();

            $address = new Address();
            $address->address_1 = $data['address_1'];
            $address->city = $data['city'];
            $address->state = $data['state'];
            $address->zip = $data['zip'];
            $address->type = 'mailing';
            $address->primary = true;
            $address->addressable()->associate($individual);
            $address->save();

            $address = new Address();
            $address->address_1 = $data['address_1'];
            $address->city = $data['city'];
            $address->state = $data['state'];
            $address->zip = $data['zip'];
            $address->type = 'billing';
            $address->primary = true;
            $address->addressable()->associate($individual);
            $address->save();
        }

        foreach ($users as $user) {

            $login = $user->logins()->where('loginable_type', 'donor')->where('loginable_id', $donor->id)->first();
            if ($login) continue;
            $login = new Login();
            $login->loginable()->associate($donor);

            $user->logins()->save($login);

            $user->currentLogin()->associate($donor);
            $user->save();
        }

        if ($flag) {
            dump($donor->id);
        }
    }

    private function updateIndividualAccount($emails, Donor $donor, $data)
    {

        $orgId = $this->argument('organization_id');

        $individual = $donor->donorProfiles()->where('organization_id', $orgId)->first();



        if ($individual &&  $individual->lgl_id && $individual->lgl_id !== $data['lgl_id']) {
            dump('this donor already has a donor profile with an lgl id', $data);
        } else {
            if (!$individual) {
                $individual = new Individual();
                $individual->first_name = $data['first_name'];
                $individual->last_name = $data['last_name'];
                $individual->email_1 = $data['email_1'];
                $individual->email_2 = $data['email_2'];
                $individual->email_3 = $data['email_3'];
                $individual->mobile_phone = $data['phone_1'];
                $individual->donor_id = $donor->id;
                $individual->organization_id = $orgId;
                $individual->lgl_id = $data['lgl_id'];
                $individual->save();

                $address = new Address();
                $address->address_1 = $data['address_1'];
                $address->city = $data['city'];
                $address->state = $data['state'];
                $address->zip = $data['zip'];
                $address->type = 'mailing';
                $address->primary = true;
                $address->addressable()->associate($individual);
                $address->save();

                $address = new Address();
                $address->address_1 = $data['address_1'];
                $address->city = $data['city'];
                $address->state = $data['state'];
                $address->zip = $data['zip'];
                $address->type = 'billing';
                $address->primary = true;
                $address->addressable()->associate($individual);
                $address->save();
            } else if ($individual) {
                $individual->lgl_id = $data['lgl_id'];
                $individual->save();
            }

            $users = [];
            foreach ($emails as $email) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    $users[] = $user;
                    continue;
                }

                $userData = ['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $email, 'phone' => $data['phone_1'], 'password' => Str::random()];

                try {
                    $users[] = User::create($userData);
                } catch (Exception $e) {
                }
            }


            foreach ($users as $user) {
                $login = new Login();

                $login->loginable()->associate($donor);

                $user->logins()->save($login);

                $user->currentLogin()->associate($donor);

                $user->save();
            }
        }
    }

    private function createCompanyAccount($emails, $data, $flag)
    {


        if (!count($emails)) return;

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $users[] = $user;
                continue;
            }

            $userData = ['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $email, 'phone' => $data['phone_1'], 'password' => Str::random()];

            try {
                $users[] = User::create($userData);
            } catch (Exception $e) {
            }
        }


        if (!count($users)) {
            dump('no users to relate');
            return;
        }

        $donor = new Donor();

        $donor->type = 'company';

        $handle = $data['organization_name'];
        $handle = str_replace(' ', '', $handle);

        $existingDonor = null;

        if ($handle) $existingDonor = Donor::where('handle', $handle)->first();

        if ($existingDonor) {
            $randomNumber = rand(1, 10000);
            $donor->handle = "{$handle}{$randomNumber}";
        } else {
            $donor->handle = "{$handle}";
        }

        $donor->save();

        $company = new Company();
        $company->name = $data['organization_name'];
        $company->email_1 = $data['email_1'];
        $company->mobile_phone = $data['phone_1'];
        $company->donor_id = $donor->id;


        $company->organization_id = env('WEGIVE_DONOR_PROFILE');
        $company->save();

        $address = new Address();
        $address->address_1 = $data['address_1'];
        $address->city = $data['city'];
        $address->state = $data['state'];
        $address->zip = $data['zip'];
        $address->type = 'mailing';
        $address->primary = true;
        $address->addressable()->associate($company);
        $address->save();

        $address = new Address();
        $address->address_1 = $data['address_1'];
        $address->city = $data['city'];
        $address->state = $data['state'];
        $address->zip = $data['zip'];
        $address->type = 'billing';
        $address->primary = true;
        $address->addressable()->associate($company);
        $address->save();

        $orgId = $this->argument('organization_id');


        if ($orgId !== "null" && $orgId && $orgId !== 'undefined') {
            $company = new Company();
            $company->name = $data['organization_name'];

            $company->email_1 = $data['email_1'];
            $company->mobile_phone = $data['phone_1'];
            $company->donor_id = $donor->id;
            $company->organization_id = $orgId;
            $company->lgl_id = $data['lgl_id'];
            $company->save();

            $address = new Address();
            $address->address_1 = $data['address_1'];
            $address->city = $data['city'];
            $address->state = $data['state'];
            $address->zip = $data['zip'];
            $address->type = 'mailing';
            $address->primary = true;
            $address->addressable()->associate($company);
            $address->save();

            $address = new Address();
            $address->address_1 = $data['address_1'];
            $address->city = $data['city'];
            $address->state = $data['state'];
            $address->zip = $data['zip'];
            $address->type = 'billing';
            $address->primary = true;
            $address->addressable()->associate($company);
            $address->save();
        }

        foreach ($users as $user) {

            $login = $user->logins()->where('loginable_type', 'donor')->where('loginable_id', $donor->id)->first();
            if ($login) continue;
            $login = new Login();
            $login->loginable()->associate($donor);

            $user->logins()->save($login);

            $user->currentLogin()->associate($donor);
            $user->save();
        }

        if ($flag) {
            dump($donor->id);
        }
    }

    private function updateCompanyAccount($emails, Donor $donor, $data)
    {

        $orgId = $this->argument('organization_id');

        $company = $donor->donorProfiles()->where('organization_id', $orgId)->first();



        if ($company &&  $company->lgl_id && $company->lgl_id !== $data['lgl_id']) {
            dump('this donor already has a donor profile with an lgl id', $data);
        } else {
            if (!$company) {
                $company = new Company();
                $company->first_name = $data['organization_name'];
                $company->email_1 = $data['email_1'];
                $company->email_2 = $data['email_2'];
                $company->email_3 = $data['email_3'];
                $company->mobile_phone = $data['phone_1'];
                $company->donor_id = $donor->id;
                $company->organization_id = $orgId;
                $company->lgl_id = $data['lgl_id'];
                $company->save();

                $address = new Address();
                $address->address_1 = $data['address_1'];
                $address->city = $data['city'];
                $address->state = $data['state'];
                $address->zip = $data['zip'];
                $address->type = 'mailing';
                $address->primary = true;
                $address->addressable()->associate($company);
                $address->save();

                $address = new Address();
                $address->address_1 = $data['address_1'];
                $address->city = $data['city'];
                $address->state = $data['state'];
                $address->zip = $data['zip'];
                $address->type = 'billing';
                $address->primary = true;
                $address->addressable()->associate($company);
                $address->save();
            } else if ($company) {
                $company->lgl_id = $data['lgl_id'];
                $company->save();
            }

            $users = [];
            foreach ($emails as $email) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    $users[] = $user;
                    continue;
                }

                $userData = ['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $email, 'phone' => $data['phone_1'], 'password' => Str::random()];

                try {
                    $users[] = User::create($userData);
                } catch (Exception $e) {
                }
            }


            foreach ($users as $user) {
                $login = new Login();

                $login->loginable()->associate($donor);

                $user->logins()->save($login);

                $user->currentLogin()->associate($donor);

                $user->save();
            }
        }
    }



    private function fileHandle()
    {
        return fopen(storage_path('lglimport.csv'), 'r');
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
        'LGl Constituent ID' => 'lgl_id',
        'Constituent Type' => 'type',
        'First Name' => 'first_name',
        'Last Name' => 'last_name',
        'Full Name' => 'name',
        'Organization Name' => 'organization_name',
        'Preferred Email' => 'email_1',
        'Pref. Phone' => 'phone_1',
        'Pref. Street' => 'address_1',
        'Pref. City' => 'city',
        'Pref. State/Province' => 'state',
        'Pref. Zip/Postal Code' => 'zip',
        'Pref. Country' => 'country',
        'Total Given' => 'total_given',
        'Email 2' => 'email_2',
        'Email 3' => 'email_3',
        'Spouse' => 'spouse_id',
        'Child' => 'child_id',
        'Employer' => 'employer_id',
        'Employee' => 'employee_id',
    ];
}
