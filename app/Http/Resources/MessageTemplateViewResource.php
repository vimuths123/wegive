<?php

namespace App\Http\Resources;


use App\Models\Element;
use App\Models\MessageTemplate;
use App\Http\Resources\CommunicationResource;
use App\Models\CustomEmailAddress;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageTemplateViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'trigger_name' => MessageTemplate::TRIGGER_MAP[$this->trigger],
            'trigger' => $this->trigger,
            'subject' => $this->subject,
            'enabled' => $this->enabled,
            'created_at' => $this->created_at,
            'type_string' => MessageTemplate::TYPE_MAP[$this->type],
            'type' => $this->type,
            'enabled_text' => $this->enabled ? 'Enabled' : 'Disabled',
            'id' => $this->id,
            'from' => $this->handleFrom(),
            'content' => $this->content,
            'total_sent' => $this->totalSent(),
            'communications' => CommunicationResource::collection($this->communications->sortByDesc('created_at')),
            'owner' => $this->owner,
            'owner_type' => $this->owner_type,
            'campaign' => $this->owner->campaign,
            'element' => $this->owner instanceof Element ? $this->owner : null,
            'email_template_id' => $this->email_template_id,
            'custom_email_domain_id' => $this->custom_email_domain_id,
            'custom_email_domain' => $this->custom_email_domain_id ? new CustomEmailDomainResource($this->customEmailDomain) : null,
            'custom_email_address' => $this->custom_email_domain_id ? new CustomEmailAddressResource($this->customEmailAddress) : null,
            'custom_email_address_id' => $this->custom_email_address_id,

        ];
    }

    public function handleFrom()
    {
        if ($this->type === MessageTemplate::TYPE_EMAIL) {
            if ($this->custom_email_address_id) {
                return  $this->customEmailAddress->handle . '@' . $this->customEmailAddress->domain->domain;
            } else {
                return 'support@mail.wegive.com';
            }
        } else {
            return '18184839725';
        }
    }
}
