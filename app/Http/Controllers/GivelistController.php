<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Card;
use App\Models\User;
use App\Models\Givelist;
use Illuminate\Http\Request;
use App\Models\ScheduledDonation;
use App\Http\Resources\GivelistViewResource;
use App\Http\Resources\GivelistTableResource;
use App\Http\Resources\FundraiserTableResource;
use App\Http\Resources\ScheduledDonationResource;

class GivelistController extends Controller
{
    public function index(Request $request)
    {
        $ids = $request->ids;
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $categoryIds = $request->category_ids;
        if (is_string($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }

        $user = $request->user('sanctum');

        $givelists = Givelist::query()
            ->when($ids, function ($query) use ($ids) {
                $query->whereIn('id', $ids);
            })
            ->when($categoryIds, function ($query) use ($categoryIds) {
                $query->whereHas('organizations.categories', function ($query) use ($categoryIds) {
                    $query->whereIn('id', $categoryIds);
                });
            })
            ->when($search = $request->search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->public()
            ->paginate();

        $givelists->map(function ($givelist) use ($user) {
            $givelist['loggedInUser'] = $user;
            return $givelist;
        });

        return GivelistTableResource::collection($givelists);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {


        $givelist = new Givelist();
        $givelist->name = $request->name;


        $givelist->description = $request->description;
        $givelist->creator()->associate(auth()->user()->currentLogin);
        $givelist->active = $request->is_active;
        $givelist->is_public = $request->is_public;

        if ($request->banner) {
            $givelist->addMediaFromRequest('banner')
                ->toMediaCollection('banner');
        }


        $givelist->save();

        $givelist->organizations()->sync($request->organizations);

        $givelist->save();
        return $givelist;
    }

    public function show(Givelist $givelist)
    {
        return new GivelistViewResource($givelist);
    }

    public function update(Request $request, Givelist $givelist)
    {


        if ($request->banner) {
            try {
                $givelist->addMediaFromRequest('banner')
                    ->toMediaCollection('banner');
            } catch (\Exception $exception) {
            }
        }

        $givelist->update($request->only(['name', 'description', 'is_public']));



        if ($request->organizations) {
            $givelist->organizations()->sync($request->organizations);
        }
        return $givelist;
    }

    public function destroy(Givelist $givelist)
    {
        return $givelist->delete();
    }

    public function categories(Givelist $givelist)
    {
        return $givelist->categories;
    }

    public function fundraisers(Givelist $givelist)
    {
        return FundraiserTableResource::collection($givelist->fundraisers()->paginate());
    }

    public function organizations(Givelist $givelist)
    {
        return $givelist->organizations;
    }


    public function impact(Givelist $givelist)
    {
        return $givelist->impact;
    }

    public function givers(Givelist $givelist)
    {
        return $givelist->givers;
    }

    public function updateOrganizations(Request $request, Givelist $givelist)
    {
        return $givelist->organizations()->sync($request->all());
    }

    public function give(Request $request, Givelist $givelist)
    {
        return $givelist->give($request);
    }


    public function updateGiving(Request $request, Givelist $givelist)
    {

        $currentLogin = auth()->user()->currentLogin;

        $scheduled_donation = $currentLogin->scheduledDonations()->where('destination_type', 'givelist')->where('destination_id', $givelist->id)->first();

        abort_unless($scheduled_donation, 404, 'Previous Scheduled Donation Does Not Exist');

        if ($request->paymentType === 'donor') {

            $sum =  $currentLogin->scheduledDonations()->where('payment_method_type', $currentLogin->getMorphClass())->sum('amount') + $request->amount;

            if ($scheduled_donation->payment_method_type === $currentLogin->getMorphClass()) {
                $sum -= $scheduled_donation->amount;
            }

            abort_if($sum > $currentLogin->walletBalance(), 400, 'The total recurring giving amount from your fund will exceed your funds balance');
        }


        $scheduled_donation->destination()->associate($givelist);
        $scheduled_donation->locked = false;
        $scheduled_donation->amount = $request->amount ?? 0;
        $scheduled_donation->frequency = ScheduledDonation::DONATION_FREQUENCY_TO_INT[$request->frequency] ?? null;
        $scheduled_donation->start_date = $request->startDate ?? null;
        $scheduled_donation->tip = $request->tip ?? 0;
        $scheduled_donation->cover_fees = $request->cover_fees ?? false;
        $scheduled_donation->fee_amount = $request->fee_amount ?? 0;

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


        if ($source && !$source instanceof User && !$source instanceof Company) {
            abort_unless($source->owner()->is(auth()->user()), 401, 'Unauthenticated');
            $scheduled_donation->paymentMethod()->associate($source);
        }

        if ($source && ($source instanceof User || $source instanceof Company)) {
            $scheduled_donation->paymentMethod()->associate($source);
        }

        $currentLogin->scheduledDonations()->save($scheduled_donation);

        return ScheduledDonationResource::collection($currentLogin->scheduledDonations()->with('destination')->get());
    }

    public function addToGiving(Request $request, Givelist $givelist)
    {
        $currentLogin = auth()->user()->currentLogin;
        $previousScheduledDonation = $currentLogin->scheduledDonations()->where('destination_type', 'givelist')->where('destination_id', $givelist->id)->first();
        abort_if($previousScheduledDonation, 400, 'Scheduled donation already exists for givelist and user.');


        if ($request->paymentType === 'donor') {

            $sum =  $currentLogin->scheduledDonations()->where('payment_method_type', $currentLogin->getMorphClass())->sum('amount') + $request->amount;



            abort_if($sum > $currentLogin->walletBalance(), 400, 'The total recurring giving amount from your fund will exceed your funds balance');
        }

        $scheduled_donation = new ScheduledDonation($request->all());

        $scheduled_donation->destination()->associate($givelist);
        $scheduled_donation->user()->associate(auth()->user());
        $scheduled_donation->locked = false;
        $scheduled_donation->amount = $request->amount ?? 0;
        $scheduled_donation->frequency = ScheduledDonation::DONATION_FREQUENCY_TO_INT[$request->frequency] ?? null;
        $scheduled_donation->start_date = $request->startDate ?? null;
        $scheduled_donation->tip = $request->tip ?? 0;
        $scheduled_donation->cover_fees = $request->cover_fees ?? false;
        $scheduled_donation->fee_amount = $request->fee_amount ?? 0;


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


        if ($source && !$source instanceof User  && !$source instanceof Company) {
            abort_unless($source->owner()->is(auth()->user()), 401, 'Unauthenticated');
            $scheduled_donation->paymentMethod()->associate($source);
        }

        if ($source && ($source instanceof User ||  $source instanceof Company)) {
            $scheduled_donation->paymentMethod()->associate($source);
        }

        $currentLogin->scheduledDonations()->save($scheduled_donation);

        return ScheduledDonationResource::collection($currentLogin->scheduledDonations()->with('destination')->get());
    }

    public function removeFromGiving(Givelist $givelist)
    {

        $currentLogin = auth()->user()->currentLogin;

        $currentLogin->scheduledDonations()->where('destination_id', $givelist->id)->where('destination_type', 'givelist')->first()->delete();



        return ScheduledDonationResource::collection($currentLogin->scheduledDonations()->with('destination')->get());
    }
}
