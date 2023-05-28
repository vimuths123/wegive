<?php

namespace App\Http\Resources;

use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {


        return [
            'id' => $this->id,
            'logins' => $this->processAvailableLogins($request),
            'user' => new UserLoginResource($this),
            'current_login' => $this->processCurrentLogin($request),
            'preferred_payment' =>  new AccountResource($this->preferredPayment),
            'accounts' => $this->accounts
        ];
    }

    private function processAvailableLogins($request = null)
    {
        if ($request && $request->headers->get('app') && $request->headers->get('app') === 'dashboard') {
            return LoginResource::collection($this->logins()->where('loginable_type', 'organization')->get());
        }

        $orgId = $request->headers->get('organization') ?? env('WEGIVE_DONOR_PROFILE');
        $logins = $this->logins()->whereIn('loginable_type', ['donor'])->get();


        $availableLogins = $logins->filter(function ($item) use ($orgId) {
            return $item->loginable->organization_id === (int) $orgId;
        })->values();



        return LoginResource::collection($availableLogins);
    }

    private function processCurrentLogin()
    {
        $currentLogin = $this->currentLogin;

        if ($currentLogin instanceof Donor) {
            return new DonorViewResource($currentLogin);
        }

        if ($currentLogin instanceof Organization) {
            return new OrganizationDashboardResource($currentLogin);
        }

        return null;
    }
}
