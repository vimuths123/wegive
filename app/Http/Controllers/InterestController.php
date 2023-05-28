<?php

namespace App\Http\Controllers;

use App\Models\Interest;
use Illuminate\Http\Request;
use App\Http\Resources\InterestResource;

class InterestController extends Controller
{
    public function store(Request $request)
    {
        $interest = new Interest($request->all());

        $currentLogin = auth()->user()->currentLogin;

        $currentLogin->interests()->save($interest);

        return  InterestResource::collection($currentLogin->interests);
    }

    public function destroy(Interest $interest)
    {

        $currentLogin = auth()->user()->currentLogin;


        abort_unless($interest->enthusiast()->is($currentLogin), 401, 'Unauthenticated');

        $interest->delete();


        return  InterestResource::collection($currentLogin->interests);
    }
}
