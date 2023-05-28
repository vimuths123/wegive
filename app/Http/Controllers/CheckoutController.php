<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Checkout;
use Illuminate\Http\Request;
use App\Http\Resources\CheckoutResource;

class CheckoutController extends Controller
{
    public function uploadBanner(Request $request, Checkout $checkout)
    {
        try {
            $checkout->addMediaFromRequest('banner')
                ->toMediaCollection('banner');
        } catch (Exception $e) {
        }

        return $checkout->getFirstMedia('banner') ?  $checkout->getFirstMedia('banner')->getUrl() : null;
    }
}
