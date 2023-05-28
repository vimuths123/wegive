<?php

namespace App\Models;

use App\Http\Resources\DonorWebhookResource;
use App\Http\Resources\TransactionWebhookResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Webhook extends Model
{
    use HasFactory;

    public const NEW_DONATION = 1;
    public const UPDATED_DONATION = 2;
    public const NEW_DONOR = 3;
    public const UPDATED_DONOR = 4;
    public const NEW_OR_UPDATED_DONOR = 5;
    public const NEW_OR_UPDATED_DONATION = 6;

    public function trigger($data)
    {
        if ($data instanceof Transaction) {

            $renderedData = new TransactionWebhookResource($data);

            $renderedData = $renderedData->toResponse(app('request'))->getData();

            $renderedData = (array) $renderedData->data;
        }

        if ($data instanceof Donor) {
            $renderedData = new DonorWebhookResource($data);

            $renderedData = $renderedData->toResponse(app('request'))->getData();

            $renderedData = (array) $renderedData->data;
        }

        return Http::post($this->url, $renderedData);
    }
}
