<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Card;
use App\Models\User;
use App\Models\Donor;
use App\Models\Login;
use App\Models\Invite;
use App\Models\Address;
use App\Models\Givelist;
use App\Models\Household;
use App\Models\Fundraiser;
use App\Processors\Tilled;
use App\Models\Transaction;
use App\Models\FollowerUser;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\ScheduledDonation;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\FeedResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\AccountResource;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\HouseholdResource;
use App\Http\Resources\UserDonorResource;
use App\Http\Resources\PostSimpleResource;
use App\Http\Resources\PublicUserResource;
use App\Http\Resources\UserSimpleResource;
use App\Http\Resources\PrivateUserResource;
use App\Http\Resources\CategoryTableResource;
use App\Http\Resources\FollowRequestResource;
use App\Http\Resources\UserDashboardResource;
use App\Http\Resources\FundraiserTableResource;
use App\Http\Resources\TransactionTableResource;
use App\Http\Resources\OrganizationTableResource;
use App\Http\Resources\ScheduledDonationResource;
use App\Models\CustomQuestion;
use App\Models\CustomQuestionAnswer;
use Exception;

class UserController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        if (!$request->search) {
            return UserSimpleResource::collection(User::query()->paginate());
        }

        return UserSimpleResource::collection(User::where(DB::raw('concat(first_name," ",last_name)'), 'like', '%' . $request->search . '%')->paginate());
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }
    public function createAddress(Request $request)
    {

        $request->validate([
            'address_1' => 'required', 'address_2' => '', 'city' => 'required', 'state' => 'required', 'zip' => 'required', 'primary' => 'required', 'type' => 'required',
        ]);

        $donorProfile = auth()->user()->currentLogin;
        $address = new Address($request->all());
        $donorProfile->addresses()->save($address);
        return $donorProfile->addresses;
    }

    public function updateAddress(Request $request, Address $address)
    {
        $request->validate([
            'address_1' => 'required', 'city' => 'required', 'state' => 'required', 'zip' => 'required', 'primary' => 'required', 'type' => 'required',
        ]);

        $address->update($request->all());
        $address->save();

        $donorProfile = auth()->user()->currentLogin;
        return $donorProfile->addresses;
    }

    public function deleteAddress(Request $request, Address $address)
    {
        $address->delete();
        $donorProfile = auth()->user()->currentLogin;
        return $donorProfile->addresses;
    }

    public function taxDocuments(Request $request)
    {

        $currentLogin = auth()->user()->currentLogin;
        abort_unless($request->year, 400, 'A year is required');
        $organization = Organization::find($request->headers->get('organization'));
        return $currentLogin->taxDocument($request->year, $organization);
    }

    public function givingHistoryDocument(Request $request)
    {
        $request->validate([
            'year' => 'required',
        ]);
        $currentLogin = auth()->user()->currentLogin;
        $organization = Organization::find($request->headers->get('organization'));
        return $currentLogin->givingHistoryDocument($request->year, $organization);
    }

    public function setCurrentLogin(Request $request)
    {
        $login = auth()->user()->logins()->where('loginable_type', $request->type)->where('loginable_id', $request->id)->firstOrFail();
        $login->last_login_at = now();
        auth()->user()->currentLogin()->associate($login->loginable);
        auth()->user()->save();
        $login->save();

        return new UserResource(auth()->user());
    }

    public function showCurrentUser(Request $request)
    {
        $invite = Invite::where('token', $request->invite_token)->first();
        $user = auth()->user();
        if ($invite) {
            $user = $invite->accept($user);
            $invite->delete();
        }

        return new UserResource($user);
    }

    public function totalFundraisedGraph(Request $request)
    {
        $currentLogin = auth()->user()->currentLogin;

        return $currentLogin->totalFundraisedGraph($request->year);
    }

    public function totalGivenGraph(Request $request)
    {

        $currentLogin = auth()->user()->currentLogin;

        return $currentLogin->totalGivenGraph($request->year);
    }

    public function updateGivelistRecurringGiving(Request $request)
    {
        $user = auth()->user();


        if (!$request->startDate) {
            $scheduledDonations = $user->scheduledDonations()->where('platform', 'givelist')->get();


            foreach ($scheduledDonations as $scheduledDonation) {
                $scheduledDonation->start_date = null;
                $scheduledDonation->save();
            }
            return;
        }

        $request->validate([
            'paymentMethodType' => 'required',
            'paymentMethodId' => 'required',
            'amount' => 'required',
            'frequency' => 'required',
            'startDate' => 'required',
        ]);



        $paymentMethod = null;

        switch ($request->paymentMethodType) {
            case 'bank':
                $bank = Bank::find($request->paymentMethodId);
                $paymentMethod = $bank;
            case 'card':
                $card = Card::find($request->paymentMethodId);
                $paymentMethod = $card;
        }


        if ($request->paymentMethodType === 'balance') {
            $paymentMethod = $user;
        }

        abort_unless($paymentMethod, 400, 'A payment method is required.');

        $scheduledDonations = $user->scheduledDonations()->where('platform', 'givelist')->get();

        $increaseRatio = 1;
        if (isset($request->amount)) {
            $increaseRatio = $request->amount / $user->scheduled_donation_amount;
            $user->scheduled_donation_amount = $request->amount;
            $user->save();
        }


        foreach ($scheduledDonations as $scheduledDonation) {
            $scheduledDonation->start_date = $request->startDate;
            $scheduledDonation->frequency = User::DONATION_STRING_MAP[$request->frequency];
            $scheduledDonation->paymentMethod()->associate($paymentMethod);
            $scheduledDonation->amount = $scheduledDonation->amount * $increaseRatio;
            $scheduledDonation->save();
        }

        return ScheduledDonationResource::collection(auth()->user()->scheduledDonations->where('platform', 'givelist'));
    }

    public function updateCurrentLogin(Request $request)
    {
        $login = $request->only(['first_name', 'last_name', 'email', 'phone']);

        auth()->user()->update($login);

        return new UserResource(auth()->user());
    }

    public function updateCurrentUser(Request $request)
    {



        $currentLogin = auth()->user()->currentLogin;
        $donorData = $request->only(['avatar', 'handle', 'facebook_link', 'twitter_link', 'linkedin_link']);

        $donorProfileData = $request->only(['first_name', 'name', 'last_name', 'email_1', 'email_2', 'email_3', 'mobile_phone', 'office_phone', 'home_phone', 'fax', 'other_phone', 'profile_privacy', 'dollar_amount_privacy', 'include_name', 'include_profile_picture', 'desktop_notifications', 'mobile_notifications', 'email_notifications', 'sms_notifications', 'general_communication', 'marketing_communication', 'donation_updates_receipts', 'impact_stories_use_of_funds', 'is_public', 'matching', 'matching_percent', 'max_match_amount']);


        if (isset($donorProfileData['is_public'])) {
            $donorProfileData['is_public'] = $donorProfileData['is_public'] ? now() : null;
        }
        try {
            $currentLogin->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        } catch (Exception $e) {
        }



        if (isset($donorProfileData['mobile_phone'])) {
            $donorProfileData['mobile_phone'] =  preg_replace('/[^0-9]/', '', $donorProfileData['mobile_phone']);
        }


        if (isset($donorProfileData['home_phone'])) {
            $donorProfileData['home_phone'] =  preg_replace('/[^0-9]/', '', $donorProfileData['home_phone']);
        }


        if (isset($donorProfileData['office_phone'])) {
            $donorProfileData['office_phone'] =  preg_replace('/[^0-9]/', '', $donorProfileData['office_phone']);
        }


        if (isset($donorProfileData['fax'])) {
            $donorProfileData['fax'] =  preg_replace('/[^0-9]/', '', $donorProfileData['fax']);
        }


        if (isset($donorProfileData['other_phone'])) {
            $donorProfileData['other_phone'] =  preg_replace('/[^0-9]/', '', $donorProfileData['other_phone']);
        }





        try {
            $currentLogin->update($donorData);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

        try {
            $currentLogin->update($donorProfileData);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

        return new UserResource(auth()->user());
    }

    public function showOtherUser(User $user)
    {
        if ($user->is_public || auth()->user()->isFollowing($user)) {
            return new PublicUserResource($user);
        }

        return new PrivateUserResource($user);
    }

    public function showDonor(Organization $organization, User $user)
    {
        abort_unless($organization->donors()->contains($user), 403, 'Unauthorized');


        return (new UserDonorResource($user))->organization($organization);
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }

    public function categories(User $user)
    {
        $categories = $user->categories ?? collect();

        return CategoryTableResource::collection($categories);
    }

    public function myCategories()
    {
        return CategoryTableResource::collection(auth()->user()->preferredCategories);
    }

    public function recommendedCharities()
    {
        $currentLogin = auth()->user()->currentLogin;
        $response =  Http::get('https://api.humanitas.ai/v1/recommend?key=' . config('services.humanitas.token') . '&zip=' . $currentLogin->zip);

        $details = $response->json();

        $nonProfits = $details['nonprofits'];

        foreach ($nonProfits as &$org) {
            $ourData = Organization::search($org['ein'])->get()->first();


            if ($ourData) {
                $org['our_data'] = new OrganizationTableResource($ourData);
            } else {
                $org['our_data'] = false;
            }
        }

        return $nonProfits;
    }

    public function contactMe(Request $request)
    {
        Mail::raw($request->getContent(), function ($message) {
            $message->to('Charlie@givelistapp.com')
                ->subject('WeGive Premium Contact Form Submitted');
        });

        return;
    }

    public function fundHistory()
    {
        $currentLogin = auth()->user()->currentLogin;
        return TransactionTableResource::collection($currentLogin->fundTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->orderByDesc('created_at')->get());
    }

    public function myTransactions(Request $request)
    {
        $currentLogin = auth()->user()->currentLogin;
        $organization = Organization::find($request->headers->get('organization'));
        return TransactionTableResource::collection($currentLogin->transactions($organization)->with(['source', 'destination'])->orderByDesc('created_at')->paginate());
    }

    public function myTransactionsToOrganization(Organization $organization)
    {
        $currentLogin = auth()->user()->currentLogin;
        return TransactionTableResource::collection($currentLogin->transactions()->whereHasMorph('destination', ['organization'], function ($query) use ($organization) {
            $query->where('id', $organization->id);
        })->paginate());
    }

    public function myTransactionsToGivelist(Givelist $givelist)
    {
        return auth()->user()->transactions()->whereHasMorph('destination', ['givelist'], function ($query) use ($givelist) {
            $query->where('id', $givelist->id);
        })->get();
    }

    public function transactions(User $user)
    {
        return $user->transactions;
    }

    public function givingTotals(User $user)
    {
        if (!$user) {
            $user = auth()->user();
        }

        return $user->givingTotals;
    }

    public function givingByCategory()
    {
        return auth()->user()->givingCircle();
    }

    public function impact()
    {
        return PostSimpleResource::collection(auth()->user()->impact()->paginate());
    }


    public function impactStories(User $user)
    {
        return PostSimpleResource::collection($user->impact()->paginate());
    }

    public function give(Request $request, User $user)
    {
        $source = auth()->user();
        $destination = $user;

        $transaction = Transaction::create(
            [
                'source' => $source,
                'destination' => $destination,
                'user' => auth()->user(),
                'description' => 'User to User Transfer',
                'amount' => $request->amount,
            ]
        );

        return $transaction->status;
    }

    public function giveToCharity(Request $request)
    {
        $paymentMethod = $request->get('payment_method');

        $currentLogin = auth()->user()->currentLogin;

        /** @var Givelist|Organization $model */
        $model = null;
        if ($request->organization) {
            $model = Organization::findOrFail($request->organization);
        }

        if ($request->givelist) {
            $model = Givelist::findOrFail($request->givelist);
        }

        switch ($paymentMethod) {
            case 'balance':
                abort_unless($currentLogin->walletBalance() > $request->amount, 400, 'Not enough money in wallet');

                return $model->give($currentLogin, $request->amount,  'One Time Gift', null, $request->tip, $request->givelist_id);

            case 'user':
                abort_unless($currentLogin->walletBalance() > $request->amount, 400, 'Not enough money in wallet');

                return $model->give($currentLogin, $request->amount,  'One Time Gift', null, $request->tip, $request->givelist_id);

            case 'bank':
                $bank = Bank::find($request->payment);
                abort_unless($bank, 400, 'Could not find the payment model.');
                abort_unless($bank->owner()->is($currentLogin), 400, 'User must be owner of payment');

                return $model->give($bank, $request->amount,  'One Time Gift', null, $request->tip, $request->givelist_id);

            case 'card':
                $card = Card::find($request->payment);
                abort_unless($card, 400, 'Could not find the payment model.');
                abort_unless($card->owner()->is($currentLogin), 400, 'User must be owner of payment');

                return $model->give($card, $request->amount,  'One Time Gift', null, $request->tip, $request->givelist_id);
        }

        abort(400, 'Invalid payment method');
    }

    public function giveToMultipleCharities(Request $request)
    {
        $user = auth()->user();

        $source = null;
        switch ($request->payment_method) {
            case 'wallet':
            case 'balance':
                $source = $user;
                break;

            case 'bank':
                $bank = $user->banks()->where('id', $request->payment)->first();
                abort_unless($bank, 400, 'Could not find the bank account.');
                $source = $bank;
                break;

            case 'card':
                $card = $user->cards()->where('id', $request->payment)->first();
                abort_unless($card, 400, 'Could not find the card account.');
                $source = $card;
                break;

            default:
                abort(400, 'Invalid Payment Method');
        }

        $transactions = collect();

        $organizations = $request->organizations ?? [];
        foreach ($organizations as $key => $amount) {
            $organization = Organization::query()->find($key);
            if ($organization) {
                $transactions = $transactions->add($organization->give($source, $amount, 'One Time Gift'));
            }
        }

        $givelists = $request->givelists ?? [];
        foreach ($givelists as $key => $amount) {
            $givelist = Givelist::query()->find($key);
            if ($givelist) {
                $transactions = $transactions->add($givelist->give($source, $amount, 'One Time Gift'));
            }
        }

        return $transactions;
    }

    public function scheduledDonations()
    {
        return ScheduledDonationResource::collection(auth()->user()->scheduledDonations()->where('platform', 'givelist')->with('destination')->get());
    }

    public function getPlaidLink(Request $request)
    {

        $base = 'https://production.plaid.com';

        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            $base = 'https://sandbox.plaid.com';
        }



        $paramaters = ["client_id" => config('services.plaid.client_id'), "redirect_uri" => $request->redirect_url, "webhook" => "https://sample.webhook.com",   "language" => "en", 'secret' => config('services.plaid.secret'), "products" => ["auth"], "country_codes" => ["US"], 'client_name' => 'WeGive', 'user' => ['client_user_id' => (string) ($request->user('sanctum') ?  $request->user('sanctum')->id : '1234')]];

        $response =  Http::post($base . '/link/token/create', $paramaters);
        return $response;
    }

    public function convertPublicTokenToTilled(Request $request)
    {

        $base = 'https://production.plaid.com';
        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            $base = 'https://sandbox.plaid.com';
        }

        $paramaters = ["client_id" => config('services.plaid.client_id'), 'secret' => config('services.plaid.secret'), 'public_token' =>  $request->public_token];

        $response = Http::post($base . '/item/public_token/exchange', $paramaters);

        if ($response->failed()) return response()->json($response->json(), 400);

        $accessToken = $response['access_token'];

        $accountParamaters = ["client_id" => config('services.plaid.client_id'), 'secret' => config('services.plaid.secret'), 'access_token' => $accessToken];

        $accountResponse = Http::post($base . '/auth/get', $accountParamaters);

        if ($accountResponse->failed()) return response()->json($accountResponse->json(), 400);

        $requestData = $request->all();

        $routingNumber = null;
        $accountNumber = null;
        $accountType = null;
        $bankName = null;
        $billingAddress = $request['address'];
        $lastFour = null;

        foreach ($accountResponse['accounts'] as $account) {
            if ($account['account_id'] === $request->account_id) {
                $accountType = $account['subtype'];
                $bankName = $account['name'];
                $lastFour = $account['mask'];
            }
        }

        foreach ($accountResponse['numbers']['ach'] as $account) {
            if ($account['account_id'] === $request->account_id) {
                $routingNumber = $account['routing'];
                $accountNumber = $account['account'];
            }
        }

        $vgsCardData = [
            "data" => [
                [
                    "value" => $accountNumber,
                    "classifiers" => [
                        "ach", "number"
                    ],
                    "format" => "UUID",
                    "storage" => "PERSISTENT"
                ],
                [
                    "value" => $routingNumber,
                    "classifiers" => [
                        "ach", "routing"
                    ],
                    "format" => "UUID",
                    "storage" => "PERSISTENT"
                ]
            ]
        ];

        $vgsRequest =  Http::withBasicAuth(config('services.vgs.username'), config('services.vgs.password'))->post(config('services.vgs.endpoint') . '/aliases', $vgsCardData);


        if ($vgsRequest->failed())  return response()->json($vgsRequest->json(), 400);


        $vgsTokenData = $vgsRequest->json();

        $accountToken = $vgsTokenData['data'][0]['aliases'][0]['alias'];
        $routingToken = $vgsTokenData['data'][1]['aliases'][0]['alias'];


        $tilled = new Tilled();




        $tilledRequestBody = [
            "metadata" => [],
            "type" => "ach_debit",
            "nick_name" => "string",
            "billing_details" => [
                "address" => [
                    "street" => "test",
                    "street2" => "test",
                    "city" => "test",
                    "state" => "CA",
                    "zip" =>  $requestData['address']['zip'],
                    "country" => "US"
                ],
                "email" => $request->user('sanctum')->email ?? $requestData['bankholderEmail'],
                "name" => $requestData['address']['name'],
                "phone" => "test"
            ],
            "ach_debit" => [
                "account_type" => "checking",
                "account_number" => $accountToken,
                "routing_number" => $routingToken,
                "account_holder_name" => $requestData['address']['name']
            ],

        ];

        $proxyUsername = config('services.vgs.username');
        $proxyPassword = config('services.vgs.password');

        $proxyVault = config('services.vgs.vault');
        $proxyEnvironment = config('services.vgs.environment');


        $tilledTokenRequest = $tilled->post(
            'payment-methods',
            $tilledRequestBody,
            [
                'proxy' => "https://{$proxyUsername}:{$proxyPassword}@{$proxyVault}.{$proxyEnvironment}.verygoodproxy.com:8443",
                'ssl_cert' => storage_path("vgs-{$proxyEnvironment}.pem"),
                'verify' => false
            ]

        );

        if ($tilledTokenRequest->failed()) return response()->json($tilledTokenRequest->json(), 400);

        $tilledTokenData = $tilledTokenRequest->json();

        $bank = new Bank();
        $bank->last_four = $tilledTokenData['ach_debit']['last2'];
        $bank->tl_token = $tilledTokenData['id'];
        $bank->vgs_routing_number_token = $routingToken;
        $bank->vgs_account_number_token = $accountToken;
        $bank->name = $requestData['address']['name'];
        $bank->save();

        $tilled = new Tilled();

        $user = $request->user('sanctum');

        if (!$user) {
            return $bank;
        }

        if (empty($user->tl_token)) {
            $customer = $tilled->createCustomer([
                'email' => $user->email ?? null,
                'first_name' => $user->first_name ?? null,
                'last_name' => $user->last_name ?? null,
                // 'metadata' => [], // not currently used
                'phone' => $user->phone ?? null,
            ]);

            if ($customer->failed()) {
                return response()->json($customer->json(), 400);
            }

            $user->tl_token = $customer['id'];
            $user->save();
        }

        $response = $tilled->attachPaymentMethodToCustomer($bank->tl_token, $user->tl_token);


        if ($response->failed()) {
            return response()->json($response->json(), 400);
        }
        $user->banks()->save($bank);

        $user->preferredPayment()->associate($bank);
        $user->save();
        return $bank;
    }

    public function updateWegiveConfig(Request $request)
    {
        return auth()->user()->donorSetting()->update($request->all());
    }

    public function updateScheduledDonations(Request $request)
    {
        $scheduledDonations = $request->json();

        foreach ($scheduledDonations as $scheduledDonation) {
            $model = ScheduledDonation::find($scheduledDonation['id']);

            $model->amount = $scheduledDonation['amount'];
            $model->locked = $scheduledDonation['locked'];
            $model->save();
        }

        return  ScheduledDonationResource::collection(auth()->user()->scheduledDonations()->with('destination')->get());
    }

    public function storeCard(Request $request, User $user)
    {
        $card =  Card::create($request->all());
        $tilled = new Tilled();


        if (empty($user->tl_token)) {
            $customer = $tilled->createCustomer([
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                // 'metadata' => [], // not currently used
                // 'middle_name' => '', // not stored
                'phone' => $user->phone,
            ]);


            $user->tl_token = $customer['id'];
            $user->save();
        }

        $tilled->attachPaymentMethodToCustomer($card->tl_token, $user->tl_token);
        return $card;
    }

    public function manageCharity(Request $request)
    {
        $request->validate([
            'code' => 'required'
        ]);

        $organization = Organization::where('code', $request->code)->first();

        abort_unless($organization, 401, 'Unauthorized');

        $login = new Login();
        $login->loginable()->associate($organization);
        auth()->user()->logins()->save($login);
        auth()->user()->currentLogin()->associate($organization);
        auth()->user()->save();

        return new UserResource(auth()->user());
    }

    public function applyForOnboarding(Request $request, Organization $organization)
    {

        Mail::raw(json_encode(['organization_id' => $organization->id, 'user_id' => auth()->user()->id]), function ($message) {
            $message->to(['Charlie@givelistapp.com', 'jonathan@givelistapp.com'])
                ->subject('WeGive Charity Onboarding Request');
        });

        return;
    }


    public function storeBank(Request $request, User $user)
    {
        $bank = Bank::create($request->all());

        $tilled = new Tilled();

        if (empty($user->tl_token)) {
            $customer = $tilled->createCustomer([
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                // 'metadata' => [], // not currently used
                // 'middle_name' => '', // not stored
                'phone' => $user->phone,
            ]);

            $user->tl_token = $customer['id'];
            $user->save();
        }

        $tilled->attachPaymentMethodToCustomer($bank->tl_token, $user->tl_token);
        return $bank;
    }

    public function addFunds(Request $request)
    {

        $currentLogin = auth()->user()->currentLogin;
        return $currentLogin->addFunds($request->all()['account']['type'], $request->all()['account']['id'], $request->all()['amount']);
    }

    public function changePassword(Request $request)
    {
        auth()->user()->password = Hash::make($request->all()['password']);
        auth()->user()->save();
        return auth()->user();
    }


    public function syncCards(Request $request)
    {
        $tilled = new Tilled();

        $cardsToDelete = auth()->user()->cards()->whereNotIn('id', $request->cards)->get();

        foreach ($cardsToDelete as $card) {
            $tilled->detachPaymentMethodToCustomer($card->tl_token, auth()->user()->tl_token);
            $card->delete();
        }


        return auth()->user()->accounts;
    }

    public function syncBanks(Request $request)
    {
        $tilled = new Tilled();

        $banksToDelete = auth()->user()->banks()->whereNotIn('id', $request->banks)->get();


        foreach ($banksToDelete as $bank) {
            $tilled->detachPaymentMethodToCustomer($bank->tl_token, auth()->user()->tl_token);
            $bank->delete();
        }


        return auth()->user()->accounts;
    }

    public function accounts()
    {
        $currentLogin = auth()->user();
        return $currentLogin->accounts;
    }

    public function fundraisers()
    {
        $currentLogin = auth()->user()->currentLogin;

        return FundraiserTableResource::collection($currentLogin->fundraisers()->with(['owner', 'recipient'])->paginate());
    }

    public function donorActivity(Request $request)
    {
        $actions = auth()->user()->currentLogin->actions();

        return ActivityResource::collection($actions->orderBy('created_at', 'DESC')->paginate());
    }

    public function fundraiserHistory(Request $request)
    {

        $organization = Organization::find($request->headers->get('organization'));

        $currentLogin = auth()->user()->currentLogin;

        $fundraisers = $currentLogin->expiredFundraisers($organization);

        return FundraiserTableResource::collection($fundraisers->paginate());
    }

    public function activity()
    {

        return ActivityResource::collection(auth()->user()->activity()->orderBy('created_at', 'DESC')->paginate());
    }


    public function followingActivity()
    {
        return ActivityResource::collection(auth()->user()->followingActivity()->orderBy('created_at', 'DESC')->paginate());
    }

    public function followingFeed()
    {

        $activity = User::first()->followingActivity()->paginate();
        $impact = User::first()->followingImpact()->paginate();

        $merged = $activity->merge($impact);

        return ['feed' => FeedResource::collection($merged->sortByDesc('created_at'))];
    }

    public function allFeed()
    {

        $activity = User::first()->allActivity()->paginate();
        $impact = User::first()->allImpact()->paginate();

        $merged = $activity->merge($impact);

        return ['feed' => FeedResource::collection($merged->sortByDesc('created_at'))];
    }



    public function removeCurrentLogin()
    {

        auth()->user()->currentLogin()->associate(null);
        auth()->user()->save();
        return new UserResource(auth()->user());
    }

    public function setPreferredPayment(Request $request)
    {
        $model = null;
        if ($request->all()['type'] === 'card') {
            $model = Card::find($request->all()['id']);
            abort_unless($model->owner()->is(auth()->user()), 401, 'Unauthenticated');
        }

        if ($request->all()['type'] === 'bank') {
            $model = Bank::find($request->all()['id']);
            abort_unless($model->owner()->is(auth()->user()), 401, 'Unauthenticated');
        }

        if ($request->all()['type'] === 'donor' || $request->all()['type'] === 'balance') {
            $model = null;
        }



        auth()->user()->preferredPayment()->associate($model);

        auth()->user()->save();

        return new AccountResource(auth()->user()->preferredPayment);
    }

    public function uploadAvatar(Request $request)
    {
        auth()->user()->addMediaFromRequest('file')->toMediaCollection('avatar');

        return response()->json(auth()->user()->getFirstMedia('avatar'));
    }

    public function stats()
    {
        return auth()->user()->stats();
    }

    public function setPreferredCategories(Request $request)
    {
        auth()->user()->preferredCategories()->sync($request->ids);

        return auth()->user()->preferredCategories;
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();
        $data = $request->json()->all();
        $current_password = $data["current_password"];
        $new_password = $data["new_password"];

        abort_unless(Hash::check($current_password, $user->password), 403, 'Unauthorized');
        abort_unless(!(empty($new_password) || strlen($new_password) < 8 || strlen($new_password) > 32), 403, 'Unauthorized');

        $user->password = Hash::make($new_password);
        $user->save();
    }

    public function acceptRequest($id)
    {
        $followRequest = FollowerUser::find($id);
        $followRequest->update(['accepted_at' => now()]);

        return [
            'follow_requests' => FollowRequestResource::collection(auth()->user()->followRequests()),
            'followers' => UserSimpleResource::collection(auth()->user()->followers()->whereNull('accepted_at')->whereNull('requested_at')->orWhereNotNull('requested_at')->whereNotNull('accepted_at')->get()),
            'followings' => UserSimpleResource::collection(auth()->user()->followings()->whereNull('accepted_at')->whereNull('requested_at')->orWhereNotNull('requested_at')->whereNotNull('accepted_at')->get()),
        ];
    }

    public function denyRequest($id)
    {
        $followRequest = FollowerUser::find($id);
        $followRequest->delete();

        return [
            'follow_requests' => FollowRequestResource::collection(auth()->user()->followRequests()),
            'followers' => UserSimpleResource::collection(auth()->user()->followers()->whereNull('accepted_at')->whereNull('requested_at')->whereNull('accepted_at')->whereNull('requested_at')->orWhereNotNull('accepted_at')->get()),
            'followings' => UserSimpleResource::collection(auth()->user()->followings()->whereNull('accepted_at')->whereNull('requested_at')->whereNull('accepted_at')->whereNull('requested_at')->orWhereNotNull('accepted_at')->get()),
        ];
    }

    public function getHousehold(Household $household)
    {

        abort_unless($household->members->contains(auth()->user()->currentLogin), 401, 'Unauthorized');

        return new HouseholdResource($household);
    }

    public function answerCustomQuestions(Request $request)
    {
        $questions = $request->all();

        foreach ($questions as $q) {
            $answer = new CustomQuestionAnswer();
            $answer->answer = json_encode($q['answer']);
            $answer->customQuestion()->associate(CustomQuestion::find($q['id']));
            $answer->owner()->associate(auth()->user()->currentLogin);
            $answer->save();
        }

        return;
    }

    public function attachPaymentMethodFromTransaction(Request $request, Transaction $transaction)
    {

        abort_unless($transaction->user()->is(auth()->user()), 401, 'Unauthenticated');

        $pm = $transaction->source;

        $tilled = new Tilled();

        $user = auth()->user();

        if (empty($user->tl_token)) {
            $customer = $tilled->createCustomer([
                'email' =>  $user->email ?? null,
                'first_name' =>  $user->first_name ?? null,
                'last_name' =>  $user->last_name ?? null,
                // 'metadata' => [],
                'phone' =>  $user->phone ?? null,
            ]);

            if ($customer->failed()) {

                return response()->json($customer->json(), 400);
            }

            $user->tl_token = $customer['id'];
            $user->save();
        }


        if ($pm instanceof Card) {



            $tilled = new Tilled();

            $expirationValue = $pm->expiration;

            $expArray =  explode('/', $expirationValue);

            $tilledRequestBody = [
                "metadata" => [],
                "type" => "card",
                "nick_name" => "string",
                "billing_details" => [
                    "address" => [
                        "street" => null,
                        "street2" => null,
                        "city" => null,
                        "state" =>  null,
                        "zip" =>  $pm->zip_code,
                        "country" => null
                    ],
                    "email" => auth()->user()->email,
                    "name" => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                    "phone" => null
                ],
                "card" => [
                    "cvc" =>   $pm->vgs_security_token,
                    "exp_month" => (float) $expArray[0],
                    "exp_year" =>  (float) ($expArray[1]),
                    "number" => $pm->vgs_number_token
                ],

            ];


            $proxyUsername = config('services.vgs.username');
            $proxyPassword = config('services.vgs.password');

            $proxyVault = config('services.vgs.vault');
            $proxyEnvironment = config('services.vgs.environment');


            $tilledTokenRequest = $tilled->post(
                'payment-methods',
                $tilledRequestBody,
                [
                    'proxy' => "https://{$proxyUsername}:{$proxyPassword}@{$proxyVault}.{$proxyEnvironment}.verygoodproxy.com:8443",
                    'ssl_cert' => storage_path("vgs-{$proxyEnvironment}.pem"),
                    'verify' => false
                ]
            );


            if ($tilledTokenRequest->failed()) return response()->json($tilledTokenRequest->json(), 400);

            $tilledTokenData = $tilledTokenRequest->json();

            $pm->owner()->associate($user);
            $pm->tl_token = $tilledTokenData['id'];
            $pm->save();

            $response = $tilled->attachPaymentMethodToCustomer($pm->tl_token,  $user->tl_token, false);

            if ($response->failed()) {
                $pm->owner()->associate(null);
                $pm->save();
                abort(400, $response->json()['message'] . " Please Try Again");
            }
        } else {

            $tilledRequestBody = [
                "metadata" => [],
                "type" => "ach_debit",
                "nick_name" => "string",
                "billing_details" => [
                    "address" => [
                        "street" => 'null',
                        "street2" => 'null',
                        "city" => 'null',
                        "state" =>  null,
                        "zip" =>  '91301',
                        "country" => null
                    ],
                    "email" => auth()->user()->email,
                    "name" => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                    "phone" => null
                ],
                "ach_debit" => [
                    "account_type" => "checking",
                    "account_number" => $pm->vgs_account_number_token,
                    "routing_number" => $pm->vgs_routing_number_token,
                    "account_holder_name" => auth()->user()->first_name . ' ' . auth()->user()->last_name
                ],

            ];


            $proxyUsername = config('services.vgs.username');
            $proxyPassword = config('services.vgs.password');

            $proxyVault = config('services.vgs.vault');
            $proxyEnvironment = config('services.vgs.environment');


            $tilledTokenRequest = $tilled->post(
                'payment-methods',
                $tilledRequestBody,
                [
                    'proxy' => "https://{$proxyUsername}:{$proxyPassword}@{$proxyVault}.{$proxyEnvironment}.verygoodproxy.com:8443",
                    'ssl_cert' => storage_path("vgs-{$proxyEnvironment}.pem"),
                    'verify' => false
                ]

            );

            if ($tilledTokenRequest->failed()) return response()->json($tilledTokenRequest->json(), 400);

            $tilledTokenData = $tilledTokenRequest->json();

            $pm->owner()->associate($user);
            $pm->tl_token = $tilledTokenData['id'];
            $pm->save();

            $response = $tilled->attachPaymentMethodToCustomer($pm->tl_token,  $user->tl_token, false);

            if ($response->failed()) {
                $pm->owner()->associate(null);
                $pm->save();
                abort(400, $response->json()['message'] . " Please Try Again");
            }
        }


        $user->preferredPayment()->associate($pm);
        $user->save();

        return;
    }

    public function actions(Request $request)
    {
        return [];
    }

    public function updateLogin(Request $request, Login $login)
    {
        abort_unless($login->user()->is(auth()->user()), 401, 'Unauthenticated');

        $data = $request->only(['new_donation_email', 'new_donor_email', 'new_fundraiser_email', 'notification_frequency']);

        $login->update($data);

        return new UserResource(auth()->user());
    }
}
