<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Http\Resources\BlackbaudIntegrationResource;
use App\Http\Resources\CampaignDonorPortalResource;
use App\Http\Resources\CheckoutResource;
use App\Http\Resources\DonorPerfectIntegrationResource;
use App\Http\Resources\DonorPortalResource;
use App\Http\Resources\ElementResource;
use App\Http\Resources\ElementTableResource;
use App\Http\Resources\FundraiserTableResource;
use App\Http\Resources\NeonIntegrationResource;
use App\Http\Resources\OrganizationTableResource;
use App\Http\Resources\OrganizationViewResource;
use App\Http\Resources\PostSimpleResource;
use App\Http\Resources\SalesforceIntegrationResource;
use App\Http\Resources\ScheduledDonationResource;
use App\Models\Bank;
use App\Models\Campaign;
use App\Models\Card;
use App\Models\Checkout;
use App\Models\CustomQuestion;
use App\Models\DomainAlias;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Fundraiser;
use App\Models\Login;
use App\Models\NeonMappingRule;
use App\Models\Organization;
use App\Models\Program;
use App\Models\ScheduledDonation;
use App\Models\Transaction;
use App\Models\User;
use App\Processors\Tilled;
use Exception;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\QueryBuilder;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request)
    {
        $searchedIds = [];
        if ($search = $request->search) {
            $searchedIds = Organization::search($search)->get()->pluck('id')->all();
        }

        $ids = $request->ids;
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $categoryIds = $request->category_ids;
        if (is_string($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }

        $organizations = QueryBuilder::for(Organization::class)
            ->allowedFilters(['name', 'ein'])
            ->when(!empty($searchedIds), function ($query) use ($searchedIds) {
                $query->whereIn('organizations.id', $searchedIds);
            })
            ->when($ids, function ($query) use ($ids) {
                $query->whereIn('organizations.id', $ids);
            })
            ->when($categoryIds, function ($query) use ($categoryIds) {
                $query->join('category_organization', 'organizations.id', '=', 'category_organization.organization_id');
                $query->whereIn('category_organization.category_id', $categoryIds);
            })
            ->visible()
            ->paginate()
            ->appends(request()->query());

        $user = $request->user('sanctum');

        $organizations->map(function ($organization) use ($user) {
            $organization['user'] = $user;

            return $organization;
        });

        return OrganizationTableResource::collection($organizations);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {
        return Organization::create($request->all());
    }

    public function createProgram(Request $request, Organization $organization)
    {

        $program = new Program($request->all());
        $program->organization()->associate($organization);
        $program->save();

        return new OrganizationViewResource($organization);
    }

    public function deleteProgram(Request $request, Organization $organization, Program $program)
    {
        $program->delete();

        return new OrganizationViewResource($organization);
    }

    public function updateProgram(Request $request, Organization $organization, Program $program)
    {

        $program->update($request->all());
        $program->organization()->associate($organization);
        $program->save();

        return new OrganizationViewResource($organization);
    }

    public function uploadAvatar(Request $request, Organization $organization)
    {

        try {
            $organization->addMediaFromRequest('avatar')
                ->toMediaCollection('avatar');
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

        return $organization->getFirstMedia('avatar') ? $organization->getFirstMedia('avatar')->getUrl() : null;
    }

    public function showCheckout(Request $request, Organization $organization, Checkout $checkout)
    {
        abort_unless($checkout->recipient()->is($organization), 401, 'Unauthenticated');

        return new CheckoutResource($checkout);
    }

    public function createFundraiser(Request $request, Organization $organization)
    {
        $fundraiser = new Fundraiser($request->all());

        $fundraiser->owner()->associate(auth()->user()->currentLogin);
        if ($request->has('owner') && $request->owner) {
            $fundraiser->owner_type = $request->owner['type'];
            $fundraiser->owner_id = $request->owner['id'];
        }

        if (auth()->user()->currentLogin()->is($organization)) {
            $checkout = new Checkout();
            $checkout->recipient()->associate($organization);
            $checkout->save();
            $fundraiser->checkout()->associate($checkout);
        }

        $fundraiser->recipient()->associate($organization);

        if ($request->campaign_id) {

            $campaignId = $request->campaign_id;

            $campaign = Campaign::where('slug', $campaignId)->orWhere(function ($query) use ($campaignId) {
                if (is_numeric($campaignId)) {
                    $query->where('id', $campaignId);
                }
            })->firstOrFail();

            $fundraiser->campaign()->associate($campaign);
        }

        $fundraiser->save();

        if ($request->thumbnail) {
            try {
                $fundraiser->addMediaFromRequest('thumbnail')
                    ->toMediaCollection('thumbnail');
            } catch (Exception $e) {
                $fundraiser->delete();
                abort(500, "Image Failed to Upload");
            }
        }

        return new FundraiserTableResource($fundraiser);
    }

    public function updateFundraiser(Request $request, Organization $organization, Fundraiser $fundraiser)
    {

        abort_unless($fundraiser->owner()->is(auth()->user()->currentLogin) || $fundraiser->recipient()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');

        if ($request->end) {
            $fundraiser->expiration = now();
            $fundraiser->save();
        }
        $fundraiser->update($request->all());

        if ($request->thumbnail) {
            try {
                $fundraiser->addMediaFromRequest('thumbnail')
                    ->toMediaCollection('thumbnail');
            } catch (Exception $e) {
            }
        }

        return new FundraiserTableResource($fundraiser);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(Organization $organization)
    {
        return new OrganizationViewResource($organization);
    }

    public function donorPortal(Organization $organization)
    {
        if (!$organization->donorPortal) {
            $defaultCheckout = $organization->checkouts()->create();
            $donorPortal = $organization->donorPortal()->create();
            $donorPortal->checkout()->associate($defaultCheckout);
            $donorPortal->save();
        }

        return new DonorPortalResource($organization->donorPortal);
    }

    public function updateDonorPortal(Request $request)
    {

        $organization = auth()->user()->currentLogin;
        $organization->donorPortal()->update($request->all());

        return new OrganizationViewResource($organization);
    }

    public function updateCheckout(Request $request, Organization $organization, Checkout $checkout)
    {
        abort_unless($checkout->recipient()->is(auth()->user()->currentLogin), 401, 'You do not have access to this resource');

        $checkout->update($request->all());

        if (isset($request->all()['banner'])) {
            $checkout->addMediaFromRequest('banner')->toMediaCollection('banner');
        }

        foreach ($request['custom_questions'] as $data) {

            if (array_key_exists('id', $data)) {
                $question = CustomQuestion::find($data['id']);
                $question->name = $data['name'];
                $question->input_type = $data['input_type'];
                $question->required = $data['required'];
                $question->save_type = $data['save_type'];
                $question->title = $data['title'];
                if (array_key_exists('answer_options', $data)) {
                    $question->answer_options = json_encode(explode(",", $data['answer_options']));
                }
                $question->save();
            } else {
                $question = new CustomQuestion();
                $question->name = $data['name'];
                $question->input_type = $data['input_type'];
                $question->required = $data['required'];
                $question->save_type = $data['save_type'];
                $question->title = $data['title'];
                if ($data['answer_options']) {
                    $question->answer_options = json_encode(explode(",", $data['answer_options']));
                }
                $question->checkout()->associate($checkout);
                $question->save();
            }
        }

        return new OrganizationViewResource(auth()->user()->currentLogin);
    }

    public function givelists(Organization $organization)
    {
        return $organization->givelists;
    }

    public function categories(Organization $organization)
    {
        return $organization->categories;
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Request $request, Organization $organization)
    {
        return $organization->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy(Organization $organization)
    {
        return $organization->delete();
    }

    public function givers(Organization $organization)
    {
        return $organization->givers();
    }

    public function posts(Organization $organization)
    {
        return PostSimpleResource::collection($organization->posts()->paginate());
    }

    public function getOrganizationByDomainAlias(Request $request)
    {
        $alias = $request->get('alias');

        $domainAlias = DomainAlias::where('uri', $alias)->firstOrFail();

        $organization = $domainAlias->organization;

        return new OrganizationViewResource($organization);
    }

    public function myActivity(Organization $organization)
    {

        $currentLogin = auth()->user()->currentLogin;

        return ActivityResource::collection(Activity::where([['causer_type', $currentLogin->getMorphClass()], ['causer_id', $currentLogin->id], ['subject_type', 'organization'], ['subject_id', $organization->id], ['description', 'not like', '%wallet%']])->orWhere([['subject_type', '!=', 'organization'], ['subject_type', '!=', 'givelist'], ['causer_type', $currentLogin->getMorphClass()], ['causer_id', $currentLogin->id], ['description', 'not like', '%wallet%']])->orderBy('created_at', 'DESC')->paginate());
    }

    public function impactGraph(Request $request, Organization $organization)
    {

        return $organization->userImpactGraph($request->year);
    }

    public function updateCategories(Request $request, Organization $organization)
    {
        return $organization->categories()->sync($request->all());
    }

    public function updateWegiveConfig(Request $request, Organization $organization)
    {
        return $organization->donorSetting()->update($request->all());
    }

    public function getElements(Request $request, Organization $organization)
    {

        abort_unless(auth()->user()->currentLogin()->is($organization), 401, 'Unauthorized');

        if ($request->status === 'archived') {
            $elements = $organization->elements()->withTrashedParents()->withTrashed()->whereNotNull('elements.deleted_at');
        } else {
            $elements = $organization->elements();

            if ($request->campaign) {
                $elements = $organization->campaigns()->find($request->campaign)->elements();
            }
        }

        return ElementTableResource::collection($elements->paginate());
    }

    public function updateGiving(Request $request, Organization $organization, ScheduledDonation $scheduled_donation)
    {
        $currentLogin = auth()->user()->currentLogin;

        abort_unless($scheduled_donation, 404, 'Previous Scheduled Donation Does Not Exist');
        abort_unless($scheduled_donation->user()->is(auth()->user()), 401, 'Unauthorized');

        if ($request->paymentType === 'donor') {

            $sum = $currentLogin->scheduledDonations()->where('payment_method_type', $currentLogin->getMorphClass())->sum('amount') + $request->amount;

            if ($scheduled_donation->payment_method_type === $currentLogin->getMorphClass()) {
                $sum -= $scheduled_donation->amount;;
            }

            abort_if($sum > $currentLogin->walletBalance(), 400, 'The total recurring giving amount from your fund will exceed your funds balance');
        }

        $scheduled_donation->destination()->associate($organization);
        $scheduled_donation->locked = false;
        $scheduled_donation->amount = $request->amount ?? 0;
        $scheduled_donation->frequency = ScheduledDonation::DONATION_FREQUENCY_TO_INT[$request->frequency] ?? null;
        $scheduled_donation->start_date = $request->startDate ?? null;
        $scheduled_donation->tip = $request->tip ?? 0;
        $scheduled_donation->cover_fees = $request->cover_fees ?? false;
        $scheduled_donation->fee_amount = $request->fee_amount ?? 0;
        $scheduled_donation->tribute = $request->tribute ?? false;

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

        if ($request->paymentType === 'donor') {
            $source = $currentLogin;
        }

        if ($request->paymentType === 'card') {
            $source = Card::find($request->paymentId);
        }

        if ($request->paymentType === 'bank') {
            $source = Bank::find($request->paymentId);
        }

        if ($source && !$source instanceof User) {
            abort_unless($source->owner()->is(auth()->user()), 401, 'Unauthenticated');
            $scheduled_donation->paymentMethod()->associate($source);
        }

        if ($source && ($source instanceof User)) {
            $scheduled_donation->paymentMethod()->associate($source);
        }

        $currentLogin->scheduledDonations()->save($scheduled_donation);

        return ScheduledDonationResource::collection($currentLogin->scheduledDonations()->with('destination')->get());
    }

    public function addToGiving(Request $request, Organization $organization)
    {
        $currentLogin = auth()->user()->currentLogin;

        if ($request->paymentType === 'donor') {

            $sum = $currentLogin->scheduledDonations()->where('payment_method_type', $currentLogin->getMorphClass())->sum('amount') + $request->amount;

            abort_if($sum > $currentLogin->walletBalance(), 400, 'The total recurring giving amount from your fund will exceed your funds balance');
        }

        $scheduled_donation = new ScheduledDonation($request->all());

        $scheduled_donation->destination()->associate($organization);
        $scheduled_donation->user()->associate(auth()->user());

        $scheduled_donation->locked = false;
        $scheduled_donation->amount = $request->amount ?? 0;
        $scheduled_donation->frequency = ScheduledDonation::DONATION_FREQUENCY_TO_INT[$request->frequency] ?? null;
        $scheduled_donation->start_date = $request->startDate ?? null;
        $scheduled_donation->tip = $request->tip ?? 0;
        $scheduled_donation->cover_fees = $request->cover_fees ?? false;
        $scheduled_donation->fee_amount = $request->fee_amount ?? 0;
        $scheduled_donation->tribute = $request->tribute ?? false;
        $scheduled_donation->campaign_id = $request->campaign_id ?? null;

        if ($fundraiserId = $request->fundraiser_id) {
            $fundraiser = Fundraiser::where('slug', $fundraiserId)->first();;
            $campaign = $fundraiser->campaign;
            $scheduled_donation->fundraiser()->associate($fundraiser);
            if ($campaign) {
                $scheduled_donation->campaign()->associate($campaign);
            }
        }

        if ($request->tribute) {
            $scheduled_donation->tribute_name = $request->tribute_name ?? 0;
            $scheduled_donation->tribute_message = $request->tribute_message ?? 0;
            $scheduled_donation->tribute_email = $request->tribute_email ?? 0;
        }

        $scheduled_donation->platform = $request->platform ?? 'givelist';

        $source = null;

        if ($request->paymentType === 'donor') {
            $source = $currentLogin;
        }

        if ($request->paymentType === 'card') {
            $source = Card::find($request->paymentId);
        }

        if ($request->paymentType === 'bank') {
            $source = Bank::find($request->paymentId);
        }

        if ($source && ($source instanceof Card || $source instanceof Bank)) {
            abort_unless($source->owner()->is(auth()->user()), 401, 'Unauthenticated');
            $scheduled_donation->paymentMethod()->associate($source);
        }

        if ($source && $source instanceof Donor) {
            $scheduled_donation->paymentMethod()->associate($source);
        }

        try {
            $currentLogin->scheduledDonations()->save($scheduled_donation);
        } catch (Exception $e) {
            $scheduled_donation->delete();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

        $transaction = $scheduled_donation->transactions->first();

        return ScheduledDonationResource::collection($currentLogin->scheduledDonations()->with('destination')->get())->additional(['transaction_id' => $transaction->id]);
    }

    public function removeFromGiving(Organization $organization, ScheduledDonation $scheduledDonation)
    {

        $currentLogin = auth()->user()->currentLogin;
        abort_unless($scheduledDonation->source()->is($currentLogin), 401, 'Unauthorized');

        $scheduledDonation->delete();

        return ScheduledDonationResource::collection($currentLogin->scheduledDonations()->with('destination')->get());
    }

    public function fundraisers(Request $request, Organization $org)
    {
        $fundraisers = $org->fundraisers()->with(['owner', 'recipient']);

        if ($request->view === 'my') {
            $user = $request->user('sanctum')->currentLogin ?? $request->user('sanctum');
            $fundraisers = $user->fundraisers()->where([['recipient_type', 'organization'], ['recipient_id', $org->id]]);
        }

        return FundraiserTableResource::collection($fundraisers->with(['owner', 'recipient'])->paginate());
    }

    public function stats(Organization $org)
    {

        $currentLogin = auth()->user()->currentLogin;

        return $org->userStats($currentLogin);
    }

    public function impactNumbers(Organization $org)
    {
        $impactNumbers = $org->impactNumbers;

        $currentLogin = auth()->user()->currentLogin;

        foreach ($impactNumbers as $key => $number) {

            if (!$number->userCanView($currentLogin)) {
                $impactNumbers->forget($key);
            }
        }

        return $impactNumbers;
    }

    public function giveAsGuest(Request $request, Organization $organization)
    {
        abort_unless($organization->tl_token, 500, 'Unable to give as guest to non-onboarded organization');

        $source = null;

        // Check the payment method used
        if ($request->paymentMethod) {
            if ($request->paymentMethod['type'] === 'card') {
                $source = Card::find($request->paymentMethod['id']);
            }

            if ($request->paymentMethod['type'] === 'bank') {
                $source = Bank::find($request->paymentMethod['id']);
            }
        }

        abort_unless($source, 404, 'Source Not Valid');

        $user = User::where('email', $request->email)->firstOrFail();

        if ($source->owner) {
            abort_unless($source->owner()->is($user), 401, 'Unauthorized');
        }

        $organizationId = intval($request->headers->get('organization'));

        if ($organizationId === 0 || is_null(Organization::find($organizationId))) {
            abort(400, 'Invalid Organization selection');
        }

        $availableLogins = $user->logins()->whereHasMorph('loginable', 'donor', function ($query) use ($organizationId) {
            $query->where('organization_id', $organizationId);
        })->orderByDesc('last_login_at')->get();

        if (count($availableLogins)) {
            $owner = $availableLogins->first()->loginable;
        } else {
            $owner = Donor::create([
                'first_name'      => $user->first_name,
                'last_name'       => $user->last_name,
                'email_1'         => $user->email,
                'mobile_phone'    => $user->phone,
                'organization_id' => $organizationId
            ]);

            $login = new Login();
            $login->loginable()->associate($owner);
            $user->logins()->save($login);
            $user->currentLogin()->associate($owner)->save();
        }

        $transaction = new Transaction();
        $transaction->source()->associate($source);
        $transaction->destination()->associate($organization);
        $transaction->amount = $request->amount;
        $transaction->fee = $request->tip;
        $transaction->fee_amount = $request->fee_amount;
        $transaction->cover_fees = $request->cover_fees;
        $transaction->user()->associate($user);
        $transaction->description = 'Guest Checkout';
        $transaction->guest = true;

        if ($owner) {
            $transaction->owner()->associate($owner);
        }

        $tilled = new Tilled();

        $response = $tilled->createPaymentIntent($source->tl_token, $request->amount, $organization->tl_token, [$transaction->id], $request->tip);

        $details = $response->json();

        if ($response->successful()) {
            $transaction->correlation_id = $details['id'];

            if ($details['status'] === 'succeeded') {
                $transaction->status = Transaction::STATUS_SUCCESS;
            }

            if ($details['status'] === 'processing') {
                $transaction->status = Transaction::STATUS_PROCESSING;
            }
        }

        if ($response->failed()) {
            $transaction->status = Transaction::STATUS_FAILED;
            $transaction->save();

            abort(400, $details['message'] ?? 'Unknown payment related issue');
        }
        $transaction->save();

        return $transaction;
    }

    public function give(Request $request, Organization $organization)
    {
        $source = null;

        if ($request->paymentType === 'donor') {
            $source = User::find($request->paymentId);
        }

        if ($request->paymentType === 'card') {
            $source = Card::find($request->paymentId);
        }

        if ($request->paymentType === 'bank') {
            $source = Bank::find($request->paymentId);
        }

        $currentLogin = auth()->user()->currentLogin;

        abort_unless($source->owner()->is(auth()->user()) || $source->is($currentLogin), 401, 'Unauthenticated');

        return $organization->give($source, $request->amount, 'One Time Donation', null, $request->tip, $request->givelist_id, $request->fundraiser_id, $request->fund_id, $request->anonymous, $request->cover_fees, $request->fee_amount, $request->element_id, false, null, $request->campaign_id);
    }

    public function getNeonIntegration(Request $request, Organization $organization)
    {
        $neonIntegration = auth()->user()->currentLogin->neonIntegration;
        if (!$neonIntegration) {
            $neonIntegration = auth()->user()->currentLogin->neonIntegration()->create();
        }

        return new NeonIntegrationResource($neonIntegration);
    }

    public function updateNeonIntegration(Request $request, Organization $organization)
    {
        $neonIntegration = auth()->user()->currentLogin->neonIntegration;
        $neonIntegration->update($request->all());
        $neonIntegration->save();

        return new NeonIntegrationResource($neonIntegration);
    }

    public function setNeonMappingRules(Request $request)
    {

        $organization = auth()->user()->currentLogin;

        foreach ($request->all() as $mappingData) {
            if (array_key_exists('id', $mappingData) && $mappingData['id']) {
                $mapping = NeonMappingRule::find($mappingData['id']);
                $mapping->update($mappingData);
            } else {

                $mapping = NeonMappingRule::make($mappingData);
                $mapping->organization()->associate($organization);
                $mapping->crm = 1;
                $mapping->save();
            }
        }

        return $organization->neonMappingRuFles;
    }

    public function testSalesforceIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $salesforceIntegration = $organization->salesforceIntegration;

        $response = $salesforceIntegration->test();

        if ($response->successful()) {
            return;
        }

        return response()->json([
            'message' => $response->json()
        ], 500);
    }

    public function deleteSalesforceIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $salesforceIntegration = $organization->salesforceIntegration;

        $salesforceIntegration->delete();

        return;
    }

    public function getSalesforceIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $salesforceIntegration = $organization->salesforceIntegration;
        if (!$salesforceIntegration) {
            $salesforceIntegration = $organization->salesforceIntegration()->create();
        }

        return new SalesforceIntegrationResource($salesforceIntegration);
    }

    public function updateSalesforceIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $salesforceIntegration = $organization->salesforceIntegration;
        $salesforceIntegration->update($request->all());

        return new SalesforceIntegrationResource($salesforceIntegration);
    }

    //

    public function deleteDonorPerfectIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $donorPerfectIntegration = $organization->donorPerfectIntegration;

        $donorPerfectIntegration->delete();

        return;
    }

    public function getDonorPerfectIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $donorPerfectIntegration = $organization->donorPerfectIntegration;
        if (!$donorPerfectIntegration) {
            $donorPerfectIntegration = $organization->donorPerfectIntegration()->create();
        }

        return new DonorPerfectIntegrationResource($donorPerfectIntegration);
    }

    public function updateDonorPerfectIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $donorPerfectIntegration = $organization->donorPerfectIntegration;
        $donorPerfectIntegration->update($request->all());

        return new DonorPerfectIntegrationResource($donorPerfectIntegration);
    }

    public function getElement(Request $request, Organization $organization, Element $element)
    {
        abort_unless($element->campaign->organization()->is($organization), 401, 'Unauthorized');

        return new ElementResource($element);
    }

    public function campaignProgressBar(Request $request, Organization $organization, Campaign $campaign)
    {
        abort_unless($campaign->organization()->is($organization), 401, 'Unauthenticated');

        $totalDonated = $campaign->net_donation_volume ?? 0;
        $goal = $campaign->goal ?? 0;
        $numberOfDonors = count($campaign->donors);

        return ['total_donated' => $totalDonated / 100, 'goal' => $goal / 100, 'number_of_donors' => $numberOfDonors, 'percent' => $goal ? ($totalDonated / $goal) : null];
    }

    public function showCampaign(Request $request, Organization $organization, Campaign $campaign)
    {
        abort_unless($campaign->organization()->is($organization), 401, 'Unauthenticated');

        return new CampaignDonorPortalResource($campaign);
    }

    public function testBlackbaudIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $blackbaudIntegration = $organization->blackbaudIntegration;

        $response = $blackbaudIntegration->test();

        if ($response->successful()) {
            return;
        }

        return response()->json([
            'message' => $response->json()
        ], 500);
    }

    public function deleteBlackbaudIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $blackbaudIntegration = $organization->blackbaudIntegration;

        $blackbaudIntegration->delete();

        return;
    }

    public function getBlackbaudIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $blackbaudIntegration = $organization->blackbaudIntegration;
        if (!$blackbaudIntegration) {
            $blackbaudIntegration = $organization->blackbaudIntegration()->create();
        }

        return new BlackbaudIntegrationResource($blackbaudIntegration);
    }

    public function updateBlackbaudIntegration(Request $request)
    {
        $organization = auth()->user()->currentLogin;
        $blackbaudIntegration = $organization->blackbaudIntegration;
        $blackbaudIntegration->update($request->all());

        return new BlackbaudIntegrationResource($blackbaudIntegration);
    }
}
