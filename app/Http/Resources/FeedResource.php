<?php

namespace App\Http\Resources;

use App\Models\Post;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->handleFeedItem();
    }


    private function handleFeedItem()
    {

 
        
        if ($this->resource instanceof Activity) {
 
            return new ActivityResource($this->resource);
        }

        if ($this->resource instanceof Post) {
            return new PostResource($this->resource);
        }

        return $this->resource;
    }
}
