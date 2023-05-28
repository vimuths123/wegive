<?php

namespace App\Http\Controllers;

use App\Models\Donor;
use Illuminate\Http\Request;

class DonorController extends Controller
{
    public function uploadAvatar(Request $request)
    {

        $donor = auth()->user()->currentLogin;

        try {
            $donor->addMediaFromRequest('file')
                ->toMediaCollection('avatar');
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

        return $donor->getFirstMedia('avatar') ?  $donor->getFirstMedia('avatar')->getUrl() : null;
    }
}
