<?php

namespace App\Http\Resources;

use App\Models\Givelist;
use App\Models\Organization;
use App\Models\ScheduledDonation;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $checkout =  parent::toArray($request);

        $manuallyGenerated = [
            'recipient' => $this->handleRecipient(),
            'frequency_text' => ScheduledDonation::DONATION_FREQUENCY_MAP[$this->default_frequency] ?? null,
            'banner' => $this->getFirstMedia('banner') ?  $this->getFirstMedia('banner')->getUrl() : null,
            'designations' => $this->designations(),
            'impact_number' => $this->impactNumber,
            'custom_questions' => CustomQuestionResource::collection($this->customQuestions)
        ];

        return array_merge($checkout, $manuallyGenerated);
    }

    public function handleRecipient()
    {

        if ($this->recipient instanceof Organization) {
            return new OrganizationTableResource($this->recipient);
        }


        if ($this->recipient instanceof Givelist) {
            return new GivelistTableResource($this->recipient);
        }

        return null;
    }
}
