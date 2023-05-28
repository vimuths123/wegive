<?php

namespace App\Http\Controllers;

use App\Models\DomainAlias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DomainAliasController extends Controller
{
    public function index(Request $request)
    {
        return auth()->user()->currentLogin->domainAliases;
    }

    public function store(Request $request)
    {

        abort_if($request->uri === 'app.wegive.com', 401, 'Unauthenticated');

        $domainAlias = new DomainAlias();
        $domainAlias->uri = $request->uri;
        $domainAlias->organization()->associate(auth()->user()->currentLogin);
        $domainAlias->save();

        return $domainAlias;
    }

    public function renewSiteCertificate(Request $request, DomainAlias $domainAlias)
    {

        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            return response()->json([
                'message' => "This feature is limited to WeGive Production Instances"
            ], 500);
        }


        $siteResponse = Http::withHeaders(['Authorization' => 'Bearer ' . config('services.netlify.access_token')])->get("https://api.netlify.com/api/v1/sites/ed3b0798-62c5-458d-8af5-3f6f253ee566");

        $existingAliases = $siteResponse->json()['domain_aliases'];

        $existingAliases[] = $domainAlias->uri;

        $addAliasRequest = Http::withHeaders(['Authorization' => 'Bearer ' . config('services.netlify.access_token')])->put("https://api.netlify.com/api/v1/sites/ed3b0798-62c5-458d-8af5-3f6f253ee566", ['domain_aliases' => $existingAliases]);

        $verifyDomainRequest = Http::withHeaders(['Authorization' => 'Bearer ' . config('services.netlify.access_token')])->post("https://api.netlify.com/api/v1/sites/ed3b0798-62c5-458d-8af5-3f6f253ee566/ssl/verify_custom_domain");

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . config('services.netlify.access_token')])->post("https://api.netlify.com/api/v1/sites/ed3b0798-62c5-458d-8af5-3f6f253ee566/ssl/renew");

        $data = $response->json();


        if (in_array($domainAlias->uri, $data['domains'])) {
            return;
        } else {
            return response()->json([
                'message' => "There was an error provisioning this domain...please try again in a few minutes"
            ], 500);
        }
    }
}
