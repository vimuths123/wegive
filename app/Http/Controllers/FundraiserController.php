<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Bank;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\Fundraiser;
use App\Models\Transaction;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\FundraiserViewResource;
use App\Http\Resources\FundraiserTableResource;

class FundraiserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $currentLogin = $request->user('sanctum')->currentLogin;
        $fundraisers = null;
        if ($request->view === 'created' && $currentLogin) {
            $fundraisers = $currentLogin->fundraisers();
        }

        if ($request->view === 'given') {
            $ids =  $currentLogin->donations()->whereNotNull('fundraiser_id')->pluck('fundraiser_id');

            $fundraisers = Fundraiser::whereIn('id', $ids);
        }

        return FundraiserTableResource::collection($fundraisers->paginate());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fundraiser = new Fundraiser($request->all());

        $fundraiser->owner()->associate(auth()->user()->currentLogin);

        $fundraiser->save();


        try {
            $fundraiser->addMediaFromRequest('thumbnail')
                ->toMediaCollection('thumbnail');
        } catch (Exception $e) {
        }


        return new FundraiserTableResource($fundraiser);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Fundraiser  $fundraiser
     * @return \Illuminate\Http\Response
     */
    public function show(Fundraiser $fundraiser)
    {
        return new FundraiserViewResource($fundraiser->load(['owner', 'recipient']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Fundraiser  $fundraiser
     * @return \Illuminate\Http\Response
     */
    public function edit(Fundraiser $fundraiser)
    {
        //
    }

    public function products(Organization $organization, Fundraiser $fundraiser)
    {

        return ProductResource::collection($fundraiser->products()->with(['owner'])->paginate());
    }

    public function purchases(Organization $organization, Fundraiser $fundraiser)
    {

        $productIds = $fundraiser->products->pluck('id');

        return PurchaseResource::collection(Purchase::query()->whereIn('product_id', $productIds)->with(['product', 'user', 'transaction'])->paginate());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Fundraiser  $fundraiser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Fundraiser $fundraiser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Fundraiser  $fundraiser
     * @return \Illuminate\Http\Response
     */
    public function destroy(Fundraiser $fundraiser)
    {
        //
    }

    public function give(Request $request, Fundraiser $fundraiser)
    {



        $donationAmount = $request->amount;
        $wegiveTip = $request->wegiveTip;
        $purchases = $request->purchases;
        $source = null;





        if ($request->source['type'] === 'card') {
            $source = Card::find($request->source['id']);
        }

        if ($request->source['type'] === 'bank') {
            $source = Bank::find($request->source['id']);
        }

        abort_unless($source, 404, 'Payment Method Not Found');
        abort_unless($source->owner()->is(auth()->user()->currentLogin), 404, 'Payment Method Not Found');

        $transaction = $fundraiser->recipient->give($source, $donationAmount, 'Donation to Fundraiser', null,  $wegiveTip, null);
        $transaction->fundraiser_id = $fundraiser->id;


        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            foreach ($purchases as &$purchase) {
                $transaction->purchases()->save(new Purchase(['product_id' => $purchase['product_id'], 'user_id' => auth()->user()->id, 'first_name' => $purchase['holder']['first_name'], 'last_name' => $purchase['holder']['last_name'], 'phone' => $purchase['holder']['phone'], 'email' => $purchase['holder']['email']]));
            }
        }



        $transaction->save();
        return $transaction;
    }
}
