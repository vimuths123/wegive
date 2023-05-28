<?php

namespace App\Http\Resources;

use App\Models\MessageTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageTemplateTableResource extends JsonResource
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
            'type_string' => MessageTemplate::TYPE_MAP[$this->type],
            'type' => $this->type,
            'id' => $this->id,
            'from' => $this->handleFrom(),

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
