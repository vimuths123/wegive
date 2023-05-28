<?php

namespace App\Http\Resources;

use App\Models\Element;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunicationResource extends JsonResource
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
            'donor_name' => $this->receiver->name,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'message_template' => new MessageTemplateTableResource($this->message)
        ];
    }
}
