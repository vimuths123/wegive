<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $question =  parent::toArray($request);

        $manuallyGenerated = ['answer_options' => $this->answer_options ? implode(', ', json_decode($this->answer_options)) : '', 'answer_options_array' => $this->answer_options ? json_decode($this->answer_options) : []];

        return array_merge($question, $manuallyGenerated);
    }
}
