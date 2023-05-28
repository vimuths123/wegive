<?php

namespace App\Http\Controllers;

use App\Models\ImpactNumber;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Http\Resources\ImpactNumberResource;

class ImpactNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Organization $org)
    {

        return ImpactNumberResource::collection(auth()->user()->currentLogin->impactNumbers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Organization $org)
    {
        $impactNumber = new ImpactNumber();
        $impactNumber->name = $request->name;
        $impactNumber->number = $request->number;
        $impactNumber->start_date = $request->start_date;
        $impactNumber->end_date = $request->end_date;
        $impactNumber->static = $request->type === 'static';
        $impactNumber->include_on_organization = $request->include_on_organization ?? false;

        auth()->user()->currentLogin->impactNumbers()->save($impactNumber);

        foreach ($request->groups as $group) {
            $impactNumber->viewingGroups()->create(['destination_id' => $group['id'], 'destination_type' => $group['type']]);
        }

        return new ImpactNumberResource($impactNumber);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ImpactNumber  $impactNumber
     * @return \Illuminate\Http\Response
     */
    public function show(ImpactNumber $impactNumber)
    {
        new ImpactNumberResource($impactNumber);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ImpactNumber  $impactNumber
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Organization $organization, ImpactNumber $impactNumber)
    {


        $impactNumber->name = $request->name;
        $impactNumber->number = $request->number;
        $impactNumber->start_date = $request->start_date;
        $impactNumber->end_date = $request->end_date;
        $impactNumber->static = $request->static;
        $impactNumber->include_on_organization = $request->include_on_organization ?? false;



        $impactNumber->save();
        return new ImpactNumberResource($impactNumber);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ImpactNumber  $impactNumber
     * @return \Illuminate\Http\Response
     */
    public function destroy(Organization $organization, ImpactNumber $impactNumber)
    {
        return $impactNumber->delete();
    }
}
