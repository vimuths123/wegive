<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDonorSimpleResource extends JsonResource
{
    protected $organization;

    public function organization($value)
    {
        $this->organization = $value;
        return $this;
    }
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
            'type' => 'user',
            'name' => "{$this->first_name} {$this->last_name}",
        ];
    }
}
