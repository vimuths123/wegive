<?php

namespace App\Models;

use Exception;
use Twilio\Rest\Client;
use App\Actions\Intercom;
use App\Actions\Payments;
use Illuminate\Support\Str;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use App\Jobs\ProcessNeonIntegrationDonations;
use App\Http\Controllers\MessageTemplateController;
use App\Jobs\ProcessDonorPerfectDonations;
use App\Jobs\ProcessSalesforceOpportunityIntegration;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model implements auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;


    public const STATUS_PENDING = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_CANCELLED = 3;
    public const STATUS_REFUNDED = 4;
    public const STATUS_FAILED = 5;
    public const STATUS_DISPUTED = 6;
    public const STATUS_PROCESSING = 7;

    public const STATUS_MAP = [null, 'Pending', 'Success', 'Cancelled', 'Refunded', 'Failed', 'Disputed', 'Processing'];

    public const SEARCH_ARRAY = ['{{amount}}', '{{description}}', '{{tip}}', '{{status}}', '{{paymentMethod}}', '{{donorName}}', '{{organizationName}}', '{{fundName}}', '{{fundraiserName}}', '{{recurringDonationFrequency}}', '{{coverFees}}', '{{feeAmount}}', '{{recurringDonationIteration}}', '{{tributeEmail}}', '{{tributeName}}', '{{tributeMessage}}',];


    public const TYPE_ONCE = 1;
    public const TYPE_WEEKLY = 2;
    public const TYPE_MONTHLY = 3;


    public function replaceArray()
    {
        return ["$" . number_format($this->amount / 100, 2, ".", ","), $this->description, "$" . number_format($this->fee / 100, 2, ".", ","), Transaction::STATUS_MAP[$this->status], "{$this->source()->withTrashed()->first()->name} *{$this->source()->withTrashed()->first()->last_four}", $this->owner->name, $this->destination->name, $this->fund ? $this->fund->name : null, $this->fundraiser ? $this->fundraiser->name : null, $this->scheduledDonation ? $this->scheduledDonation->frequency : null, $this->cover_fees, $this->fee_amount, $this->scheduled_donation_iteration, $this->tribute_email, $this->tribute_name, $this->tribute_message,];
    }

    public static function boot()
    {
        parent::boot();

        self::updated(
            function ($model) {
                if ($model->destination instanceof Organization && $model->status !== Transaction::STATUS_FAILED) {

                    if ($n = $model->destination->neonIntegration) {
                        ProcessNeonIntegrationDonations::dispatch($n, $model);
                    }
                    $s = $model->destination->salesforceIntegration;

                    if ($s && $s->enabled) {
                        ProcessSalesforceOpportunityIntegration::dispatch($s, $model);
                    }

                    $dp = $model->destination->donorPerfectIntegration;
                    if ($dp && $dp->enabled) {
                        ProcessDonorPerfectDonations::dispatch($dp, $model);
                    }


                    $webhooks = $model->destination->webhooks()->whereIn('action', [Webhook::UPDATED_DONATION, Webhook::NEW_OR_UPDATED_DONATION])->get();

                    foreach ($webhooks as $webhook) {
                        try {
                            $webhook->trigger($model);
                        } catch (Exception $e) {
                        }
                    }
                }
            }
        );

        self::created(function ($model) {


            if ($model->destination instanceof Organization && $model->status !== Transaction::STATUS_FAILED) {

                if ($n = $model->destination->neonIntegration) {
                    ProcessNeonIntegrationDonations::dispatch($n, $model);
                }

                $s = $model->destination->salesforceIntegration;
                if ($s && $s->enabled) {
                    if (!$model->salesforce_id) {
                        ProcessSalesforceOpportunityIntegration::dispatch($s, $model);
                    }
                }

                $dp = $model->destination->donorPerfectIntegration;
                if ($dp && $dp->enabled) {
                    ProcessDonorPerfectDonations::dispatch($dp, $model);
                }

                $webhooks = $model->destination->webhooks()->whereIn('action', [Webhook::UPDATED_DONATION, Webhook::NEW_OR_UPDATED_DONATION])->get();

                foreach ($webhooks as $webhook) {
                    try {
                        $webhook->trigger($model);
                    } catch (Exception $e) {
                        dump($e);
                    }
                }
            }

            if (!auth()->user()) return;
            $currentLogin = auth()->user()->currentLogin;

            $intercom = new Intercom();

            if ($model->guest) return;

            $destination = $model->destination_type;


            $templates = [];

            switch ($destination) {
                case 'donor':
                    if ($model->destination->is($currentLogin)) {
                        $intercom->trackEvent(array(
                            "created_at" => time(),

                            "event_name" => "funded-account",
                            "user_id" => $model->user_id,
                            "metadata" => [

                                "funded_amount" => $model->amount / 100,
                                "funded_balance" => $model->user->walletBalance() / 100
                            ]
                        ));


                        $friendlyAmount = $model->amount / 100;

                        activity()->causedBy($currentLogin)->performedOn($model->destination)->log("Deposited \${$friendlyAmount} into your wallet.");
                        return;
                    } else {
                        $intercom->trackEvent(array(
                            "created_at" => time(),

                            "event_name" => "funded-other",
                            "user_id" => $model->user_id,
                            "metadata" => [
                                "funded_other_amount" => $model->amount / 100,
                                "funded_other_recipient" => $model->destination->name,
                            ]
                        ));
                        $friendlyAmount = $model->amount / 100;
                        activity()->causedBy($currentLogin)->performedOn($model->destination)->log("Gifted \${$friendlyAmount} {$model->destination->name}'s charity fund");
                    }

                    break;

                case 'organization':

                    $message = "";

                    $name = $model->destination->dba ? $model->destination->dba : $model->destination->legal_name;

                    if ($model->fund_id) {

                        $intercom->trackEvent(array(
                            "created_at" => time(),

                            "event_name" => "made-donation-to-organization-fund",
                            "user_id" => $model->user_id,
                            "metadata" => [
                                "organization_fund_amount" => $model->amount / 100,
                                "organization_fund_recipient" => $model->destination->name,
                                "organization_fund_name" => $model->fund->name
                            ]
                        ));





                        $friendlyAmount = $model->amount / 100;
                        $fundName = $model->fund->name;
                        $dba = $model->destination->name;

                        $message = "Gave \${$friendlyAmount} to {$dba} for {$fundName}";
                    } else if ($model->fundraiser_id) {


                        $intercom->trackEvent(array(
                            "created_at" => time(),

                            "event_name" => "made-donation-to-organization-fundraiser",
                            "user_id" => $model->user_id,
                            "metadata" => [
                                "organization_fundraiser_amount" => $model->amount / 100,
                                "organization_fundraiser_recipient" => $model->destination->name,
                                "organization_fundraiser_nameorganization_recurring_" => $model->fundraiser->name
                            ]
                        ));




                        $friendlyAmount = $model->amount / 100;
                        $fundraiserName = $model->fundraiser->name;
                        $dba = $model->destination->name;



                        $message = "Gave \${$friendlyAmount} to {$fundraiserName} benefiting {$dba}.";
                    } else if ($model->scheduled_donation_id) {


                        $intercom->trackEvent(array(
                            "created_at" => time(),

                            "event_name" => "made-donation-to-organization-recurring",
                            "user_id" => $model->user_id,
                            "metadata" => [
                                "organization_recurring_amount" => $model->amount / 100,
                                "organization_recurring_recipient" => $model->destination->name,
                                "organization_recurring_frequency" => ScheduledDonation::DONATION_FREQUENCY_MAP[$model->scheduledDonation->frequency]
                            ]
                        ));



                        $friendlyAmount = $model->amount / 100;
                        $dba = $model->destination->name;
                        $frequency = ScheduledDonation::DONATION_FREQUENCY_MAP[$model->scheduledDonation->frequency];

                        $message = "Gave \${$friendlyAmount} from {$frequency} recurring donation for {$dba}.";
                    } else {

                        $intercom->trackEvent(array(
                            "created_at" => time(),

                            "event_name" => "made-donation-to-organization",
                            "user_id" => $model->user_id,
                            "metadata" => [
                                "organization_date" => date_format($model->created_at, "M j, Y"),
                                "organization_amount" => $model->amount / 100,
                                "organization_recipient" => $model->destination->name,
                                "organization_ein" => $model->destination->ein ?? '84-2054638',
                                "organization_legal_name" => $model->destination->legal_name ?? 'Givelist Foundation',
                            ]
                        ));



                        $friendlyAmount = $model->amount / 100;
                        $dba = $model->destination->name;

                        $message = "Gave \${$friendlyAmount} to {$dba}.";
                    }

                    activity()->causedBy($currentLogin)->withProperties(['amount' => $model->amount])->performedOn($model->destination)->log($message);
                    break;

                case 'givelist':


                    $message = "";

                    if ($model->scheduled_donation_id) {


                        $intercom->trackEvent(

                            array(
                                "created_at" => time(),

                                "event_name" => "made-donation-to-givelist-recurring",
                                "user_id" => $model->user_id,
                                "metadata" => [
                                    "givelist_recurring_givelist_amount" => $model->amount / 100,
                                    "givelist_recurring_givelist_recipient" => $model->destination->name,
                                    "givelist_recurring_givelist_frequency" => ScheduledDonation::DONATION_FREQUENCY_MAP[$model->scheduledDonation->frequency]
                                ]
                            )
                        );


                        $message = "{$model->owner->name()} gave to {$model->destination->name} givelist as a part of a recurring donation.";
                    } else {


                        $intercom->trackEvent(array(
                            "created_at" => time(),

                            "event_name" => "made-donation-to-givelist",
                            "user_id" => $model->user_id,
                            "metadata" => [
                                "givelist_date" => date_format($model->created_at, "M j, Y"),
                                "givelist_amount" => $model->amount / 100,
                                "givelist_recipient" => $model->destination->name,
                            ]
                        ));


                        $message = "{$model->owner->name()} gave to {$model->destination->name} givelist as a part of a one time donation.";
                    }

                    activity()->causedBy($currentLogin)->withProperties(['amount' => $model->amount])->performedOn($model->destination)->log($message);
                    break;
            }

            if ($model->status === Transaction::STATUS_FAILED) {
                $intercom->trackEvent(array(
                    "event_name" => "transaction-failed",
                    "created_at" => time(),
                    "user_id" => $model->user_id,
                    "metadata" => [
                        "failed_amount" => $model->amount / 100,
                        "failed_date" => date_format($model->created_at, 'M j, Y'),
                        "failed_from" => in_array($model->source_type, ['user', 'company']) ? "{$model->source->name}'s Charity Fund" : "{$model->source_type} ending in *{$model->source->last_four}"
                    ]
                ));
                if ($model->element) {
                    $templates = $model->element->messageTemplates()->where('enabled', true)->where('trigger', MessageTemplate::TRIGGER_DONATION_FAILED)->get();
                } else if ($model->campaign) {
                    $templates = $model->campaign->messageTemplates()->where('owner_type', 'campaign')->where('enabled', true)->where('trigger', MessageTemplate::TRIGGER_DONATION_FAILED)->get();
                } else {
                    $templates = $model->destination->messageTemplates()->where('enabled', true)->where('trigger', MessageTemplate::TRIGGER_DONATION_FAILED)->get();
                }

                if (count($templates) == 0) {
                    $templates = $model->destination->messageTemplates()->where('enabled', true)->where('trigger', MessageTemplate::TRIGGER_DONATION_FAILED)->get();
                }
            } else {
                if ($model->element) {
                    $templates = $model->element->messageTemplates()->where('enabled', true)->whereIn('trigger', [MessageTemplate::TRIGGER_RECEIPT, MessageTemplate::TRIGGER_THANK_YOU])->get();
                } else if ($model->campaign) {
                    $templates = $model->campaign->messageTemplates()->where('owner_type', 'campaign')->where('enabled', true)->whereIn('trigger', [MessageTemplate::TRIGGER_RECEIPT, MessageTemplate::TRIGGER_THANK_YOU])->get();
                } else {
                    $templates = $model->destination->messageTemplates()->where('enabled', true)->whereIn('trigger', [MessageTemplate::TRIGGER_RECEIPT, MessageTemplate::TRIGGER_THANK_YOU])->get();
                }

                if (count($templates) == 0) {
                    $templates = $model->destination->messageTemplates()->where('enabled', true)->whereIn('trigger', [MessageTemplate::TRIGGER_RECEIPT, MessageTemplate::TRIGGER_THANK_YOU])->get();
                }
            }

            foreach ($templates as $template) {
                $template->send($model);
            }
        });
    }

    // Actual user who created
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function fundraiser()
    {
        return $this->belongsTo(Fundraiser::class);
    }

    public function scheduledDonation()
    {
        return $this->belongsTo(ScheduledDonation::class);
    }

    // If the transaction is part of a group
    public function givelist()
    {
        return $this->belongsTo(Givelist::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    // From this
    public function source()
    {
        return $this->morphTo();
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    // Model it belongs to
    public function owner()
    {
        return $this->morphTo();
    }

    // To this
    public function destination()
    {
        return $this->morphTo();
    }

    public function communications()
    {
        return $this->morphMany(Communication::class, 'subject');
    }

    public static function generateUniqueCorrelationId()
    {
        $correlationId = self::generateCorrelationId();
        while (Transaction::where('correlation_id', $correlationId)->count() >= 1) {
            $correlationId = self::generateCorrelationId();
        }
        return $correlationId;
    }

    /*
     * Do not use this outside of the generateUniqueCorrelationId function.
     *
     * @internal
     */
    public static function generateCorrelationId()
    {
        return Str::random(32);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', Transaction::STATUS_FAILED);
    }

    public function scopeSuccess($query)
    {
        return $query->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING]);
    }
}
