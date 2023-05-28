<?php

namespace App\Http\Resources;

use App\Models\Donor;
use App\Models\Givelist;
use App\Models\Household;
use App\Models\Organization;
use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;

class FundraiserViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $expiration = $this->expiration ? new DateTime($this->expiration) : null;
        $createdAt = new DateTime($this->created_at);
        return [
            'id'           => $this->slug,
            'slug'         => $this->slug,
            'thumbnail'    => $this->getFirstMedia('thumbnail') ? $this->getFirstMedia('thumbnail')->getUrl() : null,
            'goal'         => round($this->goal / 100, 2),
            'total_raised' => round($this->totalRaised() / 100, 2),
            'description'  => $this->description,
            'name'         => $this->name,
            'donors'       => DonorSimpleResource::collection($this->donors),
            'location'     => $this->location,
            'created_at'   => $createdAt->format('Y-m-d H:i:s'),
            'start_date'   => $createdAt->format('Y-m-d H:i:s'),
            'expiration'   => $expiration ? $expiration->format('Y-m-d H:i:s') : null,
            'publicity'    => $this->publicity,
            'checkout'     => $this->checkout_id ? new CheckoutResource($this->checkout) : null,
            'recipient'    => $this->processRecipient(),
            'owner'        => $this->processOwner(),
            'products'     => ProductViewResource::collection($this->products),
            'donations'    => TransactionTableResource::collection($this->transactions->sortByDesc('created_at')),
            'show_leader_board' => $this->show_leader_board,
            'show_activity' => $this->show_activity,
            'campaign_id' => $this->campaign_id,
            'recent_donations' => $this->show_activity ? TransactionDonorPortalResource::collection($this->recentDonations()) : null,
            'leader_board' => $this->show_leader_board ? $this->leaderBoard() : null,


        ];
    }

    private function processRecipient()
    {
        if ($this->resource->recipient instanceof Donor) {
            return new DonorSimpleResource($this->resource->recipient);
        }

        if ($this->resource->recipient instanceof Organization) {
            return new OrganizationTableResource($this->resource->recipient);
        }

        if ($this->resource->recipient instanceof Givelist) {
            return new GivelistTableResource($this->resource->recipient);
        }

        return null;
    }

    private function processOwner()
    {
        if ($this->resource->owner instanceof Donor) {
            return new DonorSimpleResource($this->resource->owner);
        }

        if ($this->resource->owner instanceof Organization) {
            return new OrganizationTableResource($this->resource->owner);
        }

        if ($this->resource->owner instanceof Household) {
            return $this->resource->owner;
        }

        return null;
    }
}
