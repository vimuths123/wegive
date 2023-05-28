<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Bank;
use App\Models\Card;
use App\Models\Fund;
use App\Models\Post;
use App\Models\User;
use App\Models\Donor;
use App\Models\Invite;
use League\Csv\Writer;
use SplTempFileObject;
use App\Models\Address;
use App\Models\Element;
use App\Models\Campaign;
use App\Models\Checkout;
use App\Models\Fundraiser;
use App\Processors\Tilled;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\ImpactNumber;
use Illuminate\Http\Request;
use App\Models\CustomQuestion;
use App\Jobs\ExportTransactions;
use App\Models\ScheduledDonation;
use Barryvdh\DomPDF\Facade as PDF;
use App\Http\Resources\BankResource;
use App\Http\Resources\CardResource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\ElementResource;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\PostViewResource;
use App\Http\Resources\DonorViewResource;
use App\Http\Resources\PostSimpleResource;
use App\Http\Resources\UserSimpleResource;
use App\Http\Resources\DonorSimpleResource;
use App\Http\Resources\ImpactNumberResource;
use App\Http\Resources\CampaignTableResource;
use App\Http\Resources\FundraiserTableResource;
use App\Http\Resources\TransactionViewResource;
use phpseclib3\File\ASN1\Maps\OrganizationName;
use App\Http\Resources\OrganizationViewResource;
use App\Http\Resources\TransactionTableResource;
use App\Http\Resources\ScheduledDonationResource;
use App\Http\Resources\DonorOrganizationViewResource;
use App\Http\Resources\OrganizationDashboardResource;
use App\Http\Resources\ScheduledDonationOrganizationViewResource;

class NpoDashboardController extends Controller
{
    public function payments(Request $request)
    {
        $transactions = auth()->user()->currentLogin->receivedTransactions()->with('owner');

        if ($request->view === 'recurring') {
            $transactions = $transactions->whereNotNull('scheduled_donation_id');
        }

        if ($request->query('created')) {
            $created = $request->query('created');
            if (array_key_exists('start', $created)) {
                $transactions = $transactions->where('created_at', '>=', $created['start']);
            }
            if (array_key_exists('end', $created)) {
                $transactions = $transactions->where('created_at', '<=', $created['end']);
            }
        }

        if ($request->query('designation')) {
            $designations = $request->query('designation');
            $transactions = $transactions->whereIn('fund_id', $designations);
        }

        if ($request->query("campaign")) {
            $transactions = $transactions->whereIn("campaign_id", $request->query("campaign"));
        }

        if ($request->query('donor_id')) {
            $donor_id = $request->query('donor_id');
            $transactions = $transactions->where("owner_type", "donor")->where("owner_id", $donor_id);
        }

        if ($request->query('amount')) {
            $amount = $request->query('amount');
            if (array_key_exists('start', $amount)) {
                $transactions = $transactions->where('amount', '>=', $amount['start']);
            }
            if (array_key_exists('end', $amount)) {
                $transactions = $transactions->where('amount', '<=', $amount['end']);
            }
        }

        if ($request->query('fee_covered')) {
            $fee_covered = $request->query('fee_covered') === "true" ? 1 : 0;
            $transactions = $transactions->where('cover_fees', '=', $fee_covered);
        }

        if ($request->query('tribute')) {
            $tribute = $request->query('tribute') === "true" ? 1 : 0;
            $transactions = $transactions->where('tribute', '=', $tribute);
        }

        if ($request->query('source_type')) {
            $transactions = $transactions->where('source_type', '=', $request->query('source_type'));
        }

        if ($request->query('sort')) {
            $sort = $request->query('sort');
            //amount
            //created
            if (array_key_exists("amount", $sort)) {
                $direction = $sort['amount'] === 'asc' ? 'ASC' : 'DESC';
                $transactions = $transactions->orderBy("amount", $direction);
            } else if (array_key_exists("created", $sort)) {
                $direction = $sort['created'] === 'asc' ? 'ASC' : 'DESC';
                $transactions = $transactions->orderBy("created_at", $direction);
            } else if (array_key_exists("fee", $sort)) {
                $direction = $sort['fee'] === 'asc' ? 'ASC' : 'DESC';
                $transactions = $transactions->orderBy("fee_amount", $direction);
            }
        }

        return TransactionTableResource::collection($transactions->orderByDesc('created_at')->paginate());
    }

    public function createFundraiser(Request $request)
    {
        $fundraiser = new Fundraiser($request->all());

        $fundraiser->owner()->associate(auth()->user()->currentLogin);

        $fundraiser->recipient()->associate(auth()->user()->currentLogin);

        $fundraiser->save();

        if ($request->thumbnail['url']) {
            $fundraiser->addMediaFromRequest('url')
                ->toMediaCollection('thumbnail');
        }

        return new FundraiserTableResource($fundraiser);
    }

    public function payouts(Request $request)
    {
        $tilled = new Tilled();
        $body = ['limit' => 15, 'offset' => ($request->page - 1) * 15];

        $response = $tilled->getForSpecificMerchant('payouts', $body, auth()->user()->currentLogin->tl_token);

        return $response->json();
    }

    public function getPayout(Request $request, $payoutId)
    {

        $tilled = new Tilled();
        $response = $tilled->getForSpecificMerchant('payouts/' . $payoutId, [], auth()->user()->currentLogin->tl_token);

        return $response->json();
    }

    public function getPayoutStatement(Request $request, $payoutId)
    {

        $tilled = new Tilled();
        $response = $tilled->getForSpecificMerchant('payouts/' . $payoutId, [], auth()->user()->currentLogin->tl_token);

        $payout = $response->json();

        $balanceTransactions = [];
        $hasMore = true;
        $page = 1;

        while ($hasMore) {
            $body = ['limit' => 100, 'offset' => ($page - 1) * 100, 'payout_id' => $payoutId];
            $response = $tilled->getForSpecificMerchant('balance-transactions', $body, auth()->user()->currentLogin->tl_token);
            $data = $response->json();
            $hasMore = $data['has_more'];
            $balanceTransactions = array_merge($balanceTransactions, $data['items']);
        }

        $keys = array_keys(json_decode(json_encode($balanceTransactions[0]), true));
        unset($keys[7]);
        $keys[] = 'donor_name';
        $keys[] = 'donor_email';

        $csv = Writer::createFromFileObject(new SplTempFileObject());

        $csv->insertOne($keys);

        foreach ($balanceTransactions as $data) {
            $charge = $tilled->getForSpecificMerchant("charges/{$data['source_id']}", [], auth()->user()->currentLogin->tl_token);
            $chargeData = $charge->json();

            $transaction = Transaction::where('correlation_id', $chargeData['payment_intent_id'])->first();

            unset($data['fee_details']);
            $data['amount'] = (float)$data['amount'] / 100;
            $data['net'] = (float)$data['net'] / 100;
            $data['fee'] = (float)$data['fee'] / 100;

            if ($transaction) {
                $data['donor_name'] = $transaction->owner ? $transaction->owner->name : 'Unknown';
                $data['donor_email'] = $transaction->user ? $transaction->user->email : 'Unknown';
            }
            $csv->insertOne($data);
        }
        $name = Str::random(32);

        return $csv->toString();
    }

    public function getPayoutBalanceTransactions(Request $request, $payoutId)
    {

        $tilled = new Tilled();
        $body = ['limit' => 15, 'offset' => ($request->page - 1) * 15, 'payout_id' => $payoutId];

        $response = $tilled->getForSpecificMerchant('balance-transactions', $body, auth()->user()->currentLogin->tl_token);

        $balanceTransactions = $response->json();

        foreach ($balanceTransactions['items'] as &$bt) {

            $charge = $tilled->getForSpecificMerchant("charges/{$bt['source_id']}", [], auth()->user()->currentLogin->tl_token);
            $chargeData = $charge->json();

            $transaction = Transaction::where('correlation_id', $chargeData['payment_intent_id'])->first();
            $bt['donor'] = $transaction->owner;
            $bt['transaction_id'] = $transaction->id;
        }

        return $balanceTransactions;
    }

    public function tagPostDonors(Request $request, Post $post)
    {
        abort_unless($post->organization->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        foreach ($request->donors as $donor) {
            $post->donors()->attach(Donor::find($donor['id']));
        }

        $post->save();

        return new PostViewResource($post);
    }

    public function togglePostDonors(Request $request, Post $post)
    {
        abort_unless($post->organization->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        foreach ($request->donors as $donor) {
            $post->donors()->detach(Donor::find($donor['id']));
        }
        $post->save();

        return new PostViewResource($post);
    }

    public function tagImpactNumberDonors(Request $request, ImpactNumber $impactNumber)
    {
        abort_unless($impactNumber->organization->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        foreach ($request->donors as $donor) {
            $impactNumber->donors()->attach(Donor::find($donor['id']));
        }

        $impactNumber->save();

        return new ImpactNumberResource($impactNumber);
    }

    public function toggleImpactNumberDonors(Request $request, ImpactNumber $impactNumber)
    {
        abort_unless($impactNumber->organization->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        foreach ($request->donors as $donor) {
            $impactNumber->donors()->detach(Donor::find($donor['id']));
        }
        $impactNumber->save();

        return new ImpactNumberResource($impactNumber);
    }

    public function balances(Request $request)
    {
        $tilled = new Tilled();

        $body = ['created_at_gte' => date('2010-01-01')];

        $response = $tilled->getForSpecificMerchant('balance-transactions/summary', $body, auth()->user()->currentLogin->tl_token);

        $todaysDonations = auth()->user()->currentLogin->receivedTransactions()->where('created_at', '=', now());

        return ['balances' => $response->json(), 'todaysDonations' => ['amount' => $todaysDonations->sum('amount'), 'count' => count($todaysDonations->get())]];
    }

    public function uploadAvatar(Request $request)
    {
        auth()->user()->currentLogin->addMediaFromRequest('file')->toMediaCollection('avatar');

        return response()->json(auth()->user()->currentLogin->getFirstMedia('avatar'));
    }

    public function uploadBanner(Request $request)
    {
        auth()->user()->currentLogin->addMediaFromRequest('file')->toMediaCollection('banner');

        return response()->json(auth()->user()->currentLogin->getFirstMedia('banner'));
    }

    public function uploadThumbnail(Request $request)
    {
        auth()->user()->currentLogin->addMediaFromRequest('file')->toMediaCollection('thumbnail');

        return response()->json(auth()->user()->currentLogin->getFirstMedia('thumbnail'));
    }

    public function createFund(Request $request)
    {

        auth()->user()->currentLogin->funds()->save(new Fund($request->all()));

        return new OrganizationDashboardResource(auth()->user()->currentLogin);
    }

    public function updateFund(Request $request, Fund $fund)
    {

        $data = $request->only(['name', 'description', 'active']);

        $fund->update($data);
        $fund->save();

        return new OrganizationDashboardResource(auth()->user()->currentLogin);
    }

    public function disputes()
    {
        return TransactionTableResource::collection(auth()->user()->currentLogin->disputes()->with('owner')->paginate());
    }

    public function fundraisers()
    {
        $fundraisers = auth()->user()->currentLogin->fundraisers()->with(['owner', 'recipient']);

        return FundraiserTableResource::collection($fundraisers->with(['owner', 'recipient'])->orderByDesc('created_at')->paginate());
    }

    public function transactions()
    {
        return TransactionTableResource::collection(auth()->user()->currentLogin->transactions()->with('owner')->orderByDesc('created_at')->paginate());
    }

    public function recurringDonationsTotal(Request $request)
    {
        return auth()->user()->currentLogin->recurringDonationsAmount($request->start_date, $request->end_date);
    }

    public function grossDonationVolume(Request $request)
    {
        return auth()->user()->currentLogin->grossDonationsVolume($request->start_date, $request->end_date);
    }

    public function grossDonationVolumeGraph(Request $request)
    {
        return auth()->user()->currentLogin->grossDonationVolumeGraph($request->start_date, $request->end_date);
    }

    public function netDonationVolume(Request $request)
    {
        return auth()->user()->currentLogin->netDonationsVolume($request->start_date, $request->end_date);
    }

    public function newDonors(Request $request)
    {
        return auth()->user()->currentLogin->giversByTimePeriod($request->start_date, $request->end_date);
    }

    public function firstTimeDonors(Request $request)
    {
        return auth()->user()->currentLogin->firstTimeDonorsByTimePeriod($request->start_date, $request->end_date);
    }

    public function recurringDonors(Request $request)
    {
        return auth()->user()->currentLogin->recurringGiversByTimePeriod($request->start_date, $request->end_date);
    }

    public function nonRecurringDonors(Request $request)
    {
        return auth()->user()->currentLogin->nonRecurringGiversByTimePeriod($request->start_date, $request->end_date);
    }

    public function returningDonors(Request $request)
    {
        return auth()->user()->currentLogin->returningDonorsByTimePeriod($request->start_date, $request->end_date);
    }

    public function recentDonations(Request $request)
    {
        return
            TransactionTableResource::collection(auth()->user()->currentLogin->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->with('owner')->where('created_at', '>=', $request->start_date)->where('created_at', '<=', $request->end_date)->get()->sortByDesc('created_at')->take(10));
    }

    public function posts()
    {
        return PostSimpleResource::collection(auth()->user()->currentLogin->posts);
    }

    public function show()
    {
        return new OrganizationDashboardResource(auth()->user()->currentLogin);
    }

    public function showImpactNumber(Request $request, ImpactNumber $impactNumber)
    {
        return new ImpactNumberResource($impactNumber);
    }

    public function exportTransactions(Request $request)
    {

        $transactions = Transaction::whereIn('id', $request->transactions)->get();

        $keys = array_keys(json_decode(json_encode($transactions->first()), true));
        unset($keys[0]);
        unset($keys[1]);
        $keys[] = 'donor_name';
        $keys[] = "user_email";
        $keys[] = "user_phone";

        $array = [$keys];

        foreach ($transactions as $t) {
            if ($t->destination()->is(auth()->user()->currentLogin)) {
                $tData = json_decode(json_encode($t), true);
                unset($tData['id']);
                unset($tData['correlation_id']);
                $tData['name'] = $t->owner_id ? $t->owner->name : 'Guest';
                $tData['user_email'] = $t->user ? $t->user->email : null;
                $tData['user_phone'] = $t->user ? $t->user->phone : null;
                $tData['amount'] = $t->amount / 100;
                array_push($array, $tData);
            }
        }

        $fp = tmpfile();

        // Loop through file pointer and a line
        foreach ($array as $fields) {

            fputcsv($fp, $fields);
        }

        $email = auth()->user()->email;

        Mail::send('emails.brian', [], function ($message) use ($fp, $email) {
            $message->to($email)
                ->subject('Transaction List');

            $message->attach(stream_get_meta_data($fp)['uri'], ['as' => 'TransactionList.csv', 'mime' => 'text/csv']);
        });

        fclose($fp);

        return;
    }

    public function exportAllTransactions(Request $request)
    {

        ExportTransactions::dispatch(auth()->user()->currentLogin, auth()->user()->email);

        return;
    }

    public function downloadTransactionReceipt(Request $request, Transaction $transaction)
    {

        abort_unless($transaction->destination->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        $data = ['donationAmount' => round(($transaction->amount - $transaction->fee) / 100, 2), 'recipientName' => $transaction->destination->name, 'ownerName' => $transaction->owner->name, 'ein' => $transaction->direct_deposit ? $transaction->destination->ein : '84-2054638', 'directDeposit' => $transaction->direct_deposit, 'logo' => $transaction->destination->getFirstMedia('avatar') ? $transaction->destination->getFirstMedia('avatar')->getUrl() : null];
        $email = auth()->user()->email;
        $pdf = PDF::loadView('emails.receipt', $data);

        return $pdf->download('receipt.pdf');
    }

    public function showDonor(Donor $donor)
    {
        abort_unless($donor->organization_id === auth()->user()->current_login_id, 401, 'Not Authorized');

        return new DonorViewResource($donor);
    }

    public function createDonorAddress(Request $request, Donor $donor)
    {
        abort_unless($donor->organization_id === auth()->user()->current_login_id, 401, 'Not Authorized');
        $address = new Address($request->all());
        $donor->addresses()->save($address);

        return new DonorViewResource($donor);
    }

    public function updateDonorAddress(Request $request, Donor $donor, Address $address)
    {
        abort_unless($donor->organization_id === auth()->user()->current_login_id, 401, 'Not Authorized');
        abort_unless($donor->addresses->contains($address), 401, 'Unauthenticated');

        $address->update($request->all());

        return new DonorViewResource($donor);
    }

    public function deleteDonorAddress(Request $request, Donor $donor, Address $address)
    {
        abort_unless($donor->organization_id === auth()->user()->current_login_id, 401, 'Not Authorized');
        abort_unless($donor->addresses->contains($address), 401, 'Unauthenticated');

        $address->delete();

        return new DonorViewResource($donor);
    }

    public function updateDonor(Request $request, Donor $donor)
    {
        abort_unless($donor->organization_id === auth()->user()->current_login_id, 401, 'Not Authorized');

        $input = $request->only(['first_name', 'last_name', 'email_1', 'email_2', 'email_3', 'mobile_phone', 'home_phone', 'other_phone', 'fax', 'office_phone', 'enabled', 'sms_notifications', 'email_notifications', 'general_communication', 'donation_updates_receipts', 'impact_stories_use_of_funds', 'notes', 'birthdate']);

        $donor->update($input);

        return new DonorViewResource($donor);
    }

    public function getTransaction(Transaction $transaction)
    {
        abort_unless($transaction->destination()->is(auth()->user()->currentLogin), 400, 'You do not have permission to access this transaction.');

        return new TransactionViewResource($transaction);
    }

    public function update(Request $request)
    {
        auth()->user()->currentLoginData = $request->all();

        if (isset(auth()->user()->currentLoginData['avatar'])) {
            auth()->user()->currentLogin->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            unset(auth()->user()->currentLoginData["avatar"]);
        }
        auth()->user()->currentLogin->update($request->all());

        auth()->user()->currentLogin->save();

        return new OrganizationDashboardResource(auth()->user()->currentLogin);
    }

    public function addAdmin(Request $request)
    {

        abort_unless(auth()->user()->currentLogin->users->contains(auth()->user()), 401, 'You do not have the proper permissions');

        auth()->user()->currentLogin->users()->attach(User::find($request->userId));

        return new OrganizationViewResource(auth()->user()->currentLogin);
    }

    public function getCampaigns(Request $request)
    {

        $campaigns = null;

        if ($request->view === 'active') {
            $campaigns = auth()->user()->currentLogin->campaigns;
        } else {
            $campaigns = auth()->user()->currentLogin->campaigns()->withTrashed()->whereNotNull('deleted_at')->get();
        }

        return CampaignTableResource::collection($campaigns);
    }

    public function getCampaign(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->organization()->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        return new CampaignResource($campaign);
    }

    public function getCampaignElements(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->organization()->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        return $campaign->elements;
    }

    public function createCampaign(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $data = $request->only([
            'type',
            'campaign_id',
            'name',
            'goal',
            'start_date',
            'end_date',
            'fundraiser_name',
            'fundraiser_description',
            'fundraiser_donations_p2p_only',
            'fundraiser_show_leader_board',
            'fundraiser_show_activity',
            'fundraiser_show_child_fundraiser_campaign',
            'fundraiser_show_child_event_campaign',
        ]);

        $campaign = new Campaign($data);

        if ($request->has('campaign_id')) {
            $parentCampaign = Campaign::find($request->campaign_id);
            $campaign->parentCampaign()->associate($parentCampaign);
        }

        $checkout = auth()->user()->currentLogin->checkouts()->create();
        try {
            $checkout->addMediaFromRequest('banner')
                ->toMediaCollection('banner');
        } catch (Exception $e) {
        }

        $campaign->checkout()->associate($checkout);
        $campaign->organization()->associate(auth()->user()->currentLogin);
        $campaign->save();
        $element = new Element();
        $element->campaign()->associate($campaign);
        $element->elementable()->associate($checkout);
        $element->name = 'Default Checkout';
        $element->save();

        return new CampaignResource($campaign);
    }

    public function updateCampaign(Request $request, Campaign $campaign)
    {

        abort_unless($campaign->organization()->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        $data = $request->only([
            'type',
            'campaign_id',
            'name',
            'goal',
            'start_date',
            'end_date',
            'fundraiser_name',
            'fundraiser_description',
            'fundraiser_donations_p2p_only',
            'fundraiser_show_leader_board',
            'fundraiser_show_activity',
            'fundraiser_show_child_fundraiser_campaign',
            'fundraiser_show_child_event_campaign',
        ]);

        $campaign->update($data);

        $campaign->save();

        if ($request->banner) {
            try {
                $campaign->checkout->addMediaFromRequest('banner')->toMediaCollection('banner');
            } catch (Exception $e) {
            }
        }

        return new CampaignResource($campaign);
    }

    public function deleteCampaign(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->organization()->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        $elements = $campaign->elements;
        $templates = $campaign->messageTemplates();

        foreach ($elements as $e) {
            $e->delete();
        }

        foreach ($templates as $t) {
            $t->delete();
        }

        $campaign->delete();
    }

    public function getCampaignPayments(Request $request, Campaign $campaign)
    {
        $transactions = $campaign->donationsWithDescendants()->with('owner');

        if ($request->donor_id && $request->donor_type) {
            $transactions = $transactions->where('owner_id', $request->donor_id)->where('owner_type', $request->donor_type);
        }

        if ($request->view === 'recurring') {
            $transactions = $transactions->whereNotNull('scheduled_donation_id');
        }

        $filters = json_decode($request->filters);

        foreach ($filters as $filter) {
            switch ($filter->field) {
                case 'start_date':

                    if (!$filter->value) {
                        break;
                    }
                    $transactions = $transactions->where('created_at', '>=', $filter->value);
                    break;
                case 'end_date':
                    if (!$filter->value) {
                        break;
                    }
                    $transactions = $transactions->where('created_at', '<=', $filter->value);
                    break;
                case 'destination':
                    if (!$filter->value) {
                        break;
                    }
                    $transactions = $transactions->where("{$filter->value->type}_id", $filter->value->value);
                    break;
                case 'owner':
                    if (!$filter->value) {
                        break;
                    }
                    $transactions = $transactions->where('owner_type', $filter->value->type)->where("owner_id", $filter->value->value);
                    break;
            }
        }


        return TransactionTableResource::collection($transactions->orderByDesc('created_at')->paginate());
    }

    public function donors(Request $request)
    {
        $donors = null;



        switch ($request->view) {
            case 'individual':
                $donors = auth()->user()->currentLogin->donors()->where("type", "individual");
                if ($request->search) {
                    $donors = $donors->where('name', 'like', "%{$request->search}%");
                }

                if ($request->recurring == 'true') {
                    $donors = $donors->has('scheduledDonations');
                }
                break;
            case 'company':
                $donors = auth()->user()->currentLogin->donors()->where("type", "company");
                if ($request->search) {
                    $donors = $donors->where('name', 'like', "%{$request->search}%");
                }

                if ($request->recurring == 'true') {
                    $donors = $donors->has('scheduledDonations');
                }
                break;
            case 'both':
                $individualDonors = auth()->user()->currentLogin->donors()->where("type", "individual");
                $companyDonors = auth()->user()->currentLogin->donors()->where("type", "company");

                if ($request->search) {
                    $individualDonors = $individualDonors->where('name', 'like', "%{$request->search}%");
                }

                if ($request->search) {
                    $companyDonors = $companyDonors->where('name', 'like', "%{$request->search}%");
                }
                if ($request->recurring == 'true') {
                    $companyDonors = $companyDonors->has('scheduledDonations');
                    $individualDonors = $individualDonors->has('scheduledDonations');
                }

                return ['individuals' => DonorSimpleResource::collection($individualDonors->paginate()), 'companies' => DonorSimpleResource::collection($companyDonors->paginate())];

            default:
                $donors = auth()->user()->currentLogin->donors()->where("type", "individual");
                if ($request->search) {
                    $donors = $donors->where('name', 'like', "%{$request->search}%");
                }

                if ($request->recurring == 'true') {
                    $donors = $donors->has('scheduledDonations');
                }
                break;
        }



        if ($request->fundraiser) {
            $donors = Fundraiser::find($request->fundraiser)->donors;
        }

        $filters = json_decode($request->filters);

        foreach ($filters as $filter) {
            switch ($filter->field) {
                case 'start_date':

                    if (!$filter->value) break;
                    $value = $filter->value;
                    $donors = $donors->whereHas('transactions', function ($query) use ($value) {
                        $query->where('created_at', '>=', $value);
                    });
                    break;
                case 'end_date':
                    if (!$filter->value) break;

                    $value = $filter->value;
                    $donors = $donors->whereHas('transactions', function ($query) use ($value) {
                        $query->where('created_at', '<=', $value);
                    });
                    break;

                case 'destination':
                    if (!$filter->value) break;
                    $value = $filter->value->value;
                    $type = $filter->value->type;

                    $donors = $donors->whereHas('transactions', function ($query) use ($type, $value) {
                        $query->where("{$type}_id", $value);
                    });

                    break;
            }
        }



        if ($donors instanceof Collection) {
            // do nothing
        } else {
            $donors = $donors->paginate();
        }





        return DonorSimpleResource::collection($donors);
    }

    public function exportDonors(Request $request)
    {
        $donors = $request->donors;

        $ids = array_map(function ($item) {
            return json_decode($item)->id;
        }, $donors);

        $donors = Donor::whereIn('id', $ids)->get();

        if (count($donors) === 0) {
            return;
        }

        $keys = array_keys((array)json_decode($donors[0]));
        unset($keys[0]);

        $array = array($keys);

        foreach ($donors as $i) {
            $donorProfileData = json_decode(json_encode($i), true);
            unset($donorProfileData['id']);
            array_push($array, $donorProfileData);
        }

        $fp = tmpfile();

        // Loop through file pointer and a line
        foreach ($array as $fields) {
            fputcsv($fp, $fields);
        }
        $email = auth()->user()->email;

        Mail::send('emails.brian', [], function ($message) use ($fp, $email) {
            $message->to($email)
                ->subject('Individual Donor List');

            $message->attach(stream_get_meta_data($fp)['uri'], ['as' => 'DonorList.csv', 'mime' => 'text/csv']);
        });

        fclose($fp);

        return;
    }

    public function getElementPayments(Request $request, Element $element)
    {
        $transactions = $element->donations()->with('owner');

        if ($request->donor_id && $request->donor_type) {
            $transactions = $transactions->where('owner_id', $request->donor_id)->where('owner_type', $request->donor_type);
        }

        if ($request->view === 'recurring') {
            $transactions = $transactions->whereNotNull('scheduled_donation_id');
        }

        $filters = json_decode($request->filters);

        foreach ($filters as $filter) {
            switch ($filter->field) {
                case 'start_date':

                    if (!$filter->value) {
                        break;
                    }
                    $transactions = $transactions->where('created_at', '>=', $filter->value);
                    break;
                case 'end_date':
                    if (!$filter->value) {
                        break;
                    }
                    $transactions = $transactions->where('created_at', '<=', $filter->value);
                    break;
                case 'destination':
                    if (!$filter->value) {
                        break;
                    }
                    $transactions = $transactions->where("{$filter->value->type}_id", $filter->value->value);
                    break;
                case 'owner':
                    if (!$filter->value) {
                        break;
                    }
                    $transactions = $transactions->where('owner_type', $filter->value->type)->where("owner_id", $filter->value->value);
                    break;
            }
        }

        // $map = [
        //     'refunded' => Transaction::STATUS_REFUNDED,
        //     'uncaptured' => Transaction::STATUS_PENDING,
        //     'succeeded' => Transaction::STATUS_SUCCESS,
        // ];

        // if (array_key_exists($request->view, $map)) {
        //     $transactions = $transactions->where('status', $map[$request->view]);
        // }

        return TransactionTableResource::collection($transactions->orderByDesc('created_at')->paginate());
    }

    public function createElement(Request $request)
    {

        $request->validate([
            'type'     => 'required',
            'campaign' => 'required',
            'name'     => 'required'
        ]);

        $checkout = new Checkout();
        $checkout->recipient()->associate(auth()->user()->currentLogin);
        $checkout->save();

        $element = new Element();
        $element->name = $request->name;
        $element->campaign_id = $request->campaign;
        $element->elementable()->associate($checkout);
        $element->save();

        return $element;
    }

    public function getElement(Request $request, Element $element)
    {

        abort_unless($element->campaign->organization()->is(auth()->user()->currentLogin), 401, "Unauthenticated");

        return new ElementResource($element);
    }

    public function deleteInvite(Request $request, Invite $invite)
    {
        abort_unless($invite->inviter()->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        $invite->delete();

        return auth()->user()->currentLogin->invites;
    }

    public function deleteTeamMember(Request $request, User $user)
    {
        $user->logins()->where('loginable_id', auth()->user()->currentLogin->id)->where('loginable_type', 'organization')->delete();

        return UserSimpleResource::collection(auth()->user()->currentLogin->teamMembers());
    }

    public function recordDonation(Request $request, Donor $donor)
    {
        abort_unless($donor->organization()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');
        $transaction = new Transaction();
        $transaction->amount = ((float)$request->amount) * 100;
        $transaction->owner()->associate($donor);
        $transaction->destination()->associate(auth()->user()->currentLogin);
        $transaction->description = 'Offline Donation';
        $user = null;
        $transaction->user()->associate($user);
        $transaction->source()->associate(auth()->user()->currentLogin);
        $transaction->status = Transaction::STATUS_SUCCESS;
        $transaction->tribute_name = $request->tributeName;
        $transaction->tribute_email = $request->tributeEmail;
        $transaction->tribute_message = $request->tributeMessage;

        if ($request->designation) {
            $fund = auth()->user()->currentLogin->funds()->where('id', $request->designation['id'])->first();

            $transaction->fund()->associate($fund);
        }

        if ($request->campaign) {
            $campaign = auth()->user()->currentLogin->campaigns()->where('id', $request->campaign['id'])->first();

            $transaction->campaign()->associate($campaign);
        }
        $transaction->saveQuietly();

        return new TransactionTableResource($transaction);
    }

    public function getScheduledDonations(Request $request)
    {
        $donationsQuery = auth()->user()->currentLogin->scheduledDonations()->with('source');
        if ($request->has("status")) {
            $status = $request->input("status");
            if ($status == 'paused') {
                $donationsQuery = $donationsQuery->whereNotNull("paused_at");
            } else if ($status == 'expiring-soon') {
                $donationsQuery = $donationsQuery->whereNotNull("ends_at");
            } else if ($status == 'canceled') {
                $donationsQuery = $donationsQuery->withTrashed()->whereNotNull("deleted_at");
            }
        } else {
            $donationsQuery = $donationsQuery->whereNull('paused_at')->whereNull('ends_at');
        }

        if ($request->query('donor_id')) {
            $donor_id = $request->query('donor_id');
            $donationsQuery = $donationsQuery->where("owner_type", "donor")->where("owner_id", $donor_id);
        }


        if ($request->query('designation')) {
            $designations = $request->query('designation');
            $donationsQuery = $donationsQuery->whereIn('fund_id', $designations);
        }

        if ($request->query('last_processed')) {
            $last_processed = $request->query('last_processed');
            if (array_key_exists('start', $last_processed)) {
                $donationsQuery = $donationsQuery->where('start_date', '>=', $last_processed['start']);
            }
            if (array_key_exists('end', $last_processed)) {
                $donationsQuery = $donationsQuery->where('start_date', '<=', $last_processed['end']);
            }
        }
        if ($request->query('amount')) {
            $amount = $request->query('amount');
            if (array_key_exists('start', $amount)) {
                $donationsQuery = $donationsQuery->where('amount', '>=', $amount['start']);
            }
            if (array_key_exists('end', $amount)) {
                $donationsQuery = $donationsQuery->where('amount', '<=', $amount['end']);
            }
        }

        if ($request->query('fee_amount')) {
            $fee_amount = $request->query('amount');
            if (array_key_exists('start', $fee_amount)) {
                $donationsQuery = $donationsQuery->where('fee_amount', '>=', $fee_amount['start']);
            }
            if (array_key_exists('end', $fee_amount)) {
                $donationsQuery = $donationsQuery->where('fee_amount', '<=', $fee_amount['end']);
            }
        }

        if ($request->has("sort")) {
            $key = array_keys($request->sort)[0];

            if ($mapping = ScheduledDonation::SORT_MAP[$key]) {
                $donationsQuery = $donationsQuery->orderByRaw($mapping . ' ' . $request->sort[$key]);
            }


            return ScheduledDonationResource::collection($donationsQuery->paginate());
        } else {
            return ScheduledDonationResource::collection($donationsQuery->orderBy('created_at', 'desc')->paginate());
        }
    }

    public function getScheduledDonation(Request $request, ScheduledDonation $scheduledDonation)
    {
        abort_unless($scheduledDonation->destination()->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        return new ScheduledDonationOrganizationViewResource($scheduledDonation);
    }

    public function deleteCustomQuestion(Request $request, CustomQuestion $customQuestion)
    {
        abort_unless($customQuestion->checkout->recipient()->is(auth()->user()->currentLogin), 401, 'Unauthorized');

        return $customQuestion->delete();
    }

    public function addCardToDonor(Request $request, Donor $donor)
    {
        abort_unless($donor->organization()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');

        $logins = $donor->logins;
        $user = User::find($request->userId);
        $hasLogin = $user->logins()->where('loginable_type', 'donor')->where('loginable_id', $donor->id)->firstOrFail();

        if (!$user) {
            abort(500, 'No User Found');
        }

        $requestData = $request->all();

        $numberToken = $requestData['cardNumber'];
        $cscToken = $requestData['cardCvv'];

        $tilled = new Tilled();

        $expArray = explode('/', $request->cardExpiration);

        $yearLength = strlen($expArray[1]);

        $expYear = null;

        if ($yearLength === 4) {
            $expYear = $expArray[1];
        } else {
            $expYear = '20' . $expArray[1];
        }

        $tilledRequestBody = [
            "metadata"        => [],
            "type"            => "card",
            "nick_name"       => "string",
            "billing_details" => [
                "address" => [
                    "street"  => "test",
                    "street2" => "test",
                    "city"    => "test",
                    "state"   => null,
                    "zip"     => $requestData['cardZip'],
                    "country" => null
                ],
                "email"   => $user->email,
                "name"    => $requestData['cardholderName'],
                "phone"   => "test"
            ],
            "card"            => [
                "cvc"       => $cscToken,
                "exp_month" => (float)$expArray[0],
                "exp_year"  => (float)str_replace(' ', '', $expYear),
                "number"    => $numberToken
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
                'proxy'    => "https://{$proxyUsername}:{$proxyPassword}@{$proxyVault}.{$proxyEnvironment}.verygoodproxy.com:8443",
                'ssl_cert' => storage_path("vgs-{$proxyEnvironment}.pem"),
                'verify'   => false
            ]
        );

        if ($tilledTokenRequest->failed()) {
            return response()->json($tilledTokenRequest->json(), 400);
        }

        $tilledTokenData = $tilledTokenRequest->json();

        $card = new Card();
        $card->issuer = $tilledTokenData['card']['brand'];
        $card->last_four = $tilledTokenData['card']['last4'];
        $card->expiration = $tilledTokenData['card']['exp_month'] . '/' . $tilledTokenData['card']['exp_year'];
        $card->name = $requestData['cardholderName'];
        $card->tl_token = $tilledTokenData['id'];
        $card->vgs_number_token = $numberToken;
        $card->vgs_security_code_token = $cscToken;
        $card->zip_code = $requestData['cardZip'];
        $card->save();

        if (empty($user->tl_token)) {
            $customer = $tilled->createCustomer([
                'email'      => $user->email ?? null,
                'first_name' => $user->first_name ?? null,
                'last_name'  => $user->last_name ?? null,
                // 'metadata' => [],
                'phone'      => $user->phone ?? null,
            ]);

            if ($customer->failed()) {

                return response()->json($customer->json(), 400);
            }

            $user->tl_token = $customer['id'];
            $user->save();
        }

        $response = $tilled->attachPaymentMethodToCustomer($card->tl_token, $user->tl_token);

        if ($response->failed()) {

            return response()->json($response->json(), 400);
        }

        $user->cards()->save($card);

        $user->preferredPayment()->associate($card);
        $user->save();

        return new CardResource($card);
    }

    public function addBankToDonor(Request $request, Donor $donor)
    {
        abort_unless($donor->organization()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');

        $logins = $donor->logins;
        $user = User::find($request->userId);
        $hasLogin = $user->logins()->where('loginable_type', 'donor')->where('loginable_id', $donor->id)->firstOrFail();

        if (!$user) {
            abort(500, 'No User Found');
        }

        $requestData = $request->all();

        $vgsCardData = [
            "data" => [
                [
                    "value"       => $requestData['bankAccountNumber'],
                    "classifiers" => [
                        "ach", "number"
                    ],
                    "format"      => "UUID",
                    "storage"     => "PERSISTENT"
                ],
                [
                    "value"       => $requestData['bankRoutingNumber'],
                    "classifiers" => [
                        "ach", "routing"
                    ],
                    "format"      => "UUID",
                    "storage"     => "PERSISTENT"
                ]
            ]
        ];

        $vgsRequest = Http::withBasicAuth(config('services.vgs.username'), config('services.vgs.password'))->post(config('services.vgs.endpoint') . '/aliases', $vgsCardData);

        if ($vgsRequest->failed()) {
            return response()->json($vgsRequest->json(), 400);
        }

        $vgsTokenData = $vgsRequest->json();

        $accountToken = $vgsTokenData['data'][0]['aliases'][0]['alias'];
        $routingToken = $vgsTokenData['data'][1]['aliases'][0]['alias'];

        $tilled = new Tilled();

        $tilledRequestBody = [
            "metadata"        => [],
            "type"            => "ach_debit",
            "nick_name"       => "string",
            "billing_details" => [
                "address" => [
                    "street"  => "test",
                    "street2" => "test",
                    "city"    => "test",
                    "state"   => "CA",
                    "zip"     => $requestData['bankZip'],
                    "country" => "US"
                ],
                "email"   => $user->email,
                "name"    => $requestData['bankholderName'],
                "phone"   => "test"
            ],
            "ach_debit"       => [
                "account_type"        => "checking",
                "account_number"      => $requestData['bankAccountNumber'],
                "routing_number"      => $requestData['bankRoutingNumber'],
                "account_holder_name" => $requestData['bankholderName']
            ],

        ];

        $tilledTokenRequest = $tilled->post('payment-methods', $tilledRequestBody);

        if ($tilledTokenRequest->failed()) {
            return response()->json($tilledTokenRequest->json(), 400);
        }

        $tilledTokenData = $tilledTokenRequest->json();

        $bank = new Bank();
        $bank->last_four = $tilledTokenData['ach_debit']['last2'];
        $bank->tl_token = $tilledTokenData['id'];
        $bank->vgs_routing_number_token = $routingToken;
        $bank->vgs_account_number_token = $accountToken;
        $bank->name = $requestData['bankholderName'];
        $bank->save();

        $tilled = new Tilled();

        if (!$user) {
            return $bank;
        }

        if (empty($user->tl_token)) {
            $customer = $tilled->createCustomer([
                'email'      => $user->email ?? null,
                'first_name' => $user->first_name ?? null,
                'last_name'  => $user->last_name ?? null,
                // 'metadata' => [], // not currently used
                'phone'      => $user->phone ?? null,
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

        return new BankResource($bank);
    }

    public function refundTransaction(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->destination()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');

        $tilled = new Tilled();

        $response = $tilled->refundPaymentIntent(auth()->user()->currentLogin->tl_token, $transaction->correlation_id);

        if ($response->successful()) {
            $transaction->status = Transaction::STATUS_REFUNDED;
            $transaction->save();
        } else {
            return response()->json($response->json(), 500);
        }

        return new TransactionViewResource($transaction);
    }

    public function chargeDonor(Request $request, Donor $donor)
    {
        $request->validate([
            'paymentMethod' => 'required',
            'amount'        => 'required',
            'coverFees'     => 'required',
            'feeAmount'     => 'required',
            'frequency'     => 'required',
        ]);

        $organization = auth()->user()->currentLogin;

        $source = null;

        if ($request->paymentMethod['type'] === 'card') {
            $source = Card::find($request->paymentMethod['id']);
        }

        if ($request->paymentMethod['type'] === 'bank') {
            $source = Bank::find($request->paymentMethod['id']);
        }

        abort_unless($donor->organization()->is($organization), 401, 'Unauthenticated');

        $user = $source->owner;

        $hasLogin = $user->logins()->where('loginable_type', 'donor')->where('loginable_id', $donor->id)->firstOrFail();

        if (!$user) {
            abort(500, 'No User Found');
        }

        abort_unless($source->owner()->is($user), 401, 'Unauthenticated');

        if ($request->frequency === 'once') {

            $transaction = $organization->give($source, $request->amount, 'Virtual Terminal Donation', null, $request->tip, null, null, null, false, $request->coverFees, $request->feeAmount, null, true, $user);

            $transaction->owner()->associate($donor);
            $transaction->save();

            return new TransactionTableResource($transaction);
        } else {

            $request->validate([
                'startDate' => 'required',
            ]);
            $scheduled_donation = new ScheduledDonation();

            $scheduled_donation->destination()->associate($organization);
            $scheduled_donation->user()->associate($user);

            $scheduled_donation->locked = false;
            $scheduled_donation->amount = $request->amount ?? 0;
            $scheduled_donation->frequency = ScheduledDonation::DONATION_FREQUENCY_TO_INT[$request->frequency] ?? null;
            $scheduled_donation->start_date = $request->startDate ?? null;
            $scheduled_donation->tip = $request->tip ?? 0;
            $scheduled_donation->cover_fees = $request->coverFees ?? false;
            $scheduled_donation->fee_amount = $request->feeAmount ?? 0;
            $scheduled_donation->tribute = $request->tribute ?? false;

            if ($request->tribute) {
                $scheduled_donation->tribute_name = $request->tributeName ?? 0;
                $scheduled_donation->tribute_message = $request->tributeMessage ?? 0;
                $scheduled_donation->tribute_email = $request->tributeEmail ?? 0;
            }

            if ($source && ($source instanceof Card || $source instanceof Bank)) {
                abort_unless($source->owner()->is($user), 401, 'Unauthenticated');
                $scheduled_donation->paymentMethod()->associate($source);
            }

            try {
                $donor->scheduledDonations()->save($scheduled_donation);
            } catch (Exception $e) {
                $scheduled_donation->delete();

                return response()->json([
                    'message' => $e->getMessage()
                ], 500);
            }

            $transaction = $scheduled_donation->transactions->first();

            return new ScheduledDonationResource($scheduled_donation);
        }
    }

    public function updateRecurringPlan(Request $request, ScheduledDonation $scheduled_donation)
    {
        $organization = auth()->user()->currentLogin;

        abort_unless($scheduled_donation->destination()->is($organization), 401, 'Unauthorized');

        abort_unless($scheduled_donation, 404, 'Previous Scheduled Donation Does Not Exist');

        $scheduled_donation->destination()->associate($organization);
        $scheduled_donation->locked = false;
        if ($request->amount) {
            $scheduled_donation->amount = $request->amount ?? 0;
        }
        if ($request->frequency) {
            $scheduled_donation->frequency = ScheduledDonation::DONATION_FREQUENCY_TO_INT[$request->frequency] ?? null;
        }
        if ($request->start_date || $request->startDate) {
            $scheduled_donation->start_date = $request->startDate ?? $request->startDate;
        }
        if ($request->tip) {
            $scheduled_donation->tip = $request->tip ?? 0;
        }
        if ($request->cover_fees) {
            $scheduled_donation->cover_fees = $request->cover_fees ?? false;
        }
        if ($request->fee_amount) {
            $scheduled_donation->fee_amount = $request->fee_amount ?? 0;
        }
        if ($request->tribute) {
            $scheduled_donation->tribute = $request->tribute ?? false;
        }

        if (array_key_exists('paused_at', $request->all())) {
            $scheduled_donation->paused_at = $request->paused_at ? now() : null;
            $scheduled_donation->paused_type = $request->paused_type ? $request->paused_type : null;

            if ($scheduled_donation->paused_at && $request->paused_type !== ScheduledDonation::PAUSE_TYPE_INDEFINITE) {
                $months = ScheduledDonation::PAUSE_TYPE_TO_MONTHS[$scheduled_donation->paused_type];
                $scheduled_donation->paused_until = now()->modify("+ {$months} month");
            } else {
                $scheduled_donation->paused_type = null;
                $scheduled_donation->paused_until = null;
            }
        }

        if ($request->tribute) {
            $scheduled_donation->tribute_name = $request->tribute_name ?? 0;
            $scheduled_donation->tribute_message = $request->tribute_message ?? 0;
            $scheduled_donation->tribute_email = $request->tribute_email ?? 0;
        }

        $scheduled_donation->platform = $request->platform ?? 'givelist';

        $source = null;

        if ($request->paymentType === 'card') {
            $source = Card::find($request->paymentId);
        }

        if ($request->paymentType === 'bank') {
            $source = Bank::find($request->paymentId);
        }

        if ($source && !$source instanceof User && !$source instanceof Company) {
            abort_unless($source->owner()->is($scheduled_donation->user), 401, 'Unauthenticated');
            $scheduled_donation->paymentMethod()->associate($source);
        }

        if ($source && ($source instanceof User || $source instanceof Company)) {
            $scheduled_donation->paymentMethod()->associate($source);
        }

        $scheduled_donation->save();

        return $scheduled_donation;
    }

    public function removeRecurringPlan(Request $request, ScheduledDonation $scheduled_donation)
    {
        $organization = auth()->user()->currentLogin;

        abort_unless($scheduled_donation->destination()->is($organization), 401, 'Unauthorized');

        $scheduled_donation->delete();

        return;
    }
}
