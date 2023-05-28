<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomEmailAddressResource;
use Exception;
use Illuminate\Http\Request;
use App\Models\CustomEmailDomain;
use App\Processors\MailgunHelper;
use App\Http\Resources\CustomEmailDomainResource;
use App\Models\CustomEmailAddress;

class CustomEmailDomainController extends Controller
{
    public function index(Request $request)
    {
        return CustomEmailDomainResource::collection(auth()->user()->currentLogin->customEmailDomains);
    }

    public function addAddress(Request $request, CustomEmailDomain $customEmailDomain)
    {

        $request->validate(['handle' => 'required', 'display_name' => 'required']);

        $address = new CustomEmailAddress();
        $address->custom_email_domain_id = $customEmailDomain->id;
        $address->handle = $request->handle;
        $address->display_name = $request->display_name;

        $address->save();

        return new CustomEmailAddressResource($address);
    }

    public function updateAddress(Request $request, CustomEmailDomain $customEmailDomain, CustomEmailAddress $customEmailAddress)
    {

        $data = $request->only(['handle', 'display_name']);

        $customEmailAddress->update($data);

        $customEmailAddress->save();

        return new CustomEmailAddressResource($customEmailAddress);
    }

    public function deleteAddress(Request $request, CustomEmailDomain $customEmailDomain, CustomEmailAddress $customEmailAddress)
    {

        abort_unless($customEmailAddress->domain()->is($customEmailDomain), 401, 'Unauthenticated');
        abort_unless($customEmailDomain->organization()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');
        $customEmailAddress->delete();

        return;
    }


    public function show(Request $request, CustomEmailDomain $customEmailDomain)
    {
        abort_unless($customEmailDomain->organization()->is(auth()->user()->currentLogin), 401, 'Unauthenticated');

        return new CustomEmailDomainResource($customEmailDomain);
    }

    public function createCustomDomain(Request $request)
    {

        abort_if(str_contains($request->domain, 'mail.wegive.com'), 401, 'Unauthenticated');

        $customEmailDomain = new CustomEmailDomain();

        $customEmailDomain->domain = $request->domain;
        $customEmailDomain->organization()->associate(auth()->user()->currentLogin);
        $customEmailDomain->save();

        $mg = new MailgunHelper();

        $domain = null;

        try {
            $domain = $mg->getDomain($customEmailDomain);
        } catch (Exception $e) {
            $domain = $mg->createDomain($customEmailDomain);
        }

        return new CustomEmailDomainResource($customEmailDomain);
    }
}
