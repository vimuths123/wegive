<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Card;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ScheduledDonation;
use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduledDonationResource;

class ScheduledDonationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $currentLogin = auth()->user()->currentLogin;
        return ScheduledDonationResource::collection($currentLogin->scheduled_donations);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ScheduledDonation $scheduledDonation)
    {
        return $scheduledDonation;
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ScheduledDonation $scheduledDonation)
    {

        $currentLogin = auth()->user()->currentLogin;
        $scheduledDonation->amount = $request->amount ?? 0;
        $scheduledDonation->frequency = ScheduledDonation::DONATION_FREQUENCY_TO_INT[$request->frequency] ?? null;
        $scheduledDonation->start_date = $request->startDate ?? null;
        $scheduledDonation->tip = $request->tip ?? null;
        $scheduledDonation->cover_fees = $request->cover_fees ?? null;
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


        if ($source) {
            abort_unless($source->owner()->is(auth()->user()) || $source->is($currentLogin), 401, 'Unauthenticated');
            $scheduledDonation->paymentMethod()->associate($source);
        }

        $scheduledDonation->save();

        return new ScheduledDonationResource($scheduledDonation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ScheduledDonation $scheduledDonation)
    {
        return $scheduledDonation->delete();
    }

    public function updateMany(Request $request)
    {
        return auth()->user()->scheduled_donations->upsert($request->all(), ['id'], ['amount', 'locked']);
    }
}
