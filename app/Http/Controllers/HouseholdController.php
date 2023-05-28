<?php

namespace App\Http\Controllers;

use App\Models\Household;
use Illuminate\Http\Request;
use App\Http\Resources\HouseholdResource;

class HouseholdController extends Controller
{
    public function updateHousehold(Request $request, Household $household)
    {
        $data = $request->only(['type', 'name']);

        $members = array_column($request->members, 'id');

        $household->update($data);

        $household->members()->sync($members);

        $household->save();

        return new HouseholdResource($household);
    }

    public function createHousehold(Request $request)
    {
        $data = $request->only(['type', 'name']);

        $members = array_column($request->members, 'id');
        
        $household = Household::create($data);

        $household->members()->sync($members);

        $household->save();

        return new HouseholdResource($household);
    }
}
