<?php

namespace App\Models;

use App\Jobs\ProcessSalesforceCampaignIntegration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Campaign extends Model
{
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $fillable = [
        'type',
        'campaign_id',
        'name',
        'goal',
        'start_date',
        'end_date',
        'fundraiser_name',
        'fundraiser_description',
        'fundraiser_donations_p2p_only',
        'fundraiser_show_leader_board',
        'fundraiser_show_activity',
        'fundraiser_show_child_fundraiser_campaign',
        'fundraiser_show_child_event_campaign',
    ];



    public static function boot()
    {
        parent::boot();

        self::updated(
            function ($model) {
                $s = $model->organization->salesforceIntegration;
                if ($s && $s->enabled) {
                    ProcessSalesforceCampaignIntegration::dispatch($s, $model);
                }
            }
        );

        self::created(function ($model) {
            $s = $model->organization->salesforceIntegration;
            if ($s && $s->enabled) {

                if (!$model->salesforce_id) {
                    ProcessSalesforceCampaignIntegration::dispatch($s, $model);
                }
            }



            $receiptMessageTemplate = new MessageTemplate();
            $receiptMessageTemplate->type = MessageTemplate::TYPE_EMAIL;
            $receiptMessageTemplate->enabled = false;
            $receiptMessageTemplate->owner()->associate($model);
            $receiptMessageTemplate->trigger = MessageTemplate::TRIGGER_RECEIPT;
            $receiptMessageTemplate->subject = MessageTemplate::TRIGGER_MAP[MessageTemplate::TRIGGER_RECEIPT];
            $receiptMessageTemplate->content = " ";
            $receiptMessageTemplate->save();


            $recurringDonationCreatedMessageTemplate = new MessageTemplate();
            $recurringDonationCreatedMessageTemplate->type = MessageTemplate::TYPE_EMAIL;
            $recurringDonationCreatedMessageTemplate->enabled = false;
            $recurringDonationCreatedMessageTemplate->owner()->associate($model);
            $recurringDonationCreatedMessageTemplate->trigger = MessageTemplate::TRIGGER_RECURRING_DONATION_CREATED;
            $recurringDonationCreatedMessageTemplate->subject = MessageTemplate::TRIGGER_MAP[MessageTemplate::TRIGGER_RECURRING_DONATION_CREATED];
            $recurringDonationCreatedMessageTemplate->content = " ";
            $recurringDonationCreatedMessageTemplate->save();


            $donationFailedMessageTemplate = new MessageTemplate();
            $donationFailedMessageTemplate->type = MessageTemplate::TYPE_EMAIL;
            $donationFailedMessageTemplate->enabled = false;
            $donationFailedMessageTemplate->owner()->associate($model);
            $donationFailedMessageTemplate->trigger = MessageTemplate::TRIGGER_DONATION_FAILED;
            $donationFailedMessageTemplate->subject = MessageTemplate::TRIGGER_MAP[MessageTemplate::TRIGGER_DONATION_FAILED];
            $donationFailedMessageTemplate->content = " ";
            $donationFailedMessageTemplate->save();


            $thankYouTextTemplate = new MessageTemplate();
            $thankYouTextTemplate->type = MessageTemplate::TYPE_TEXT;
            $thankYouTextTemplate->enabled = false;
            $thankYouTextTemplate->owner()->associate($model);
            $thankYouTextTemplate->trigger = MessageTemplate::TRIGGER_THANK_YOU;
            $thankYouTextTemplate->subject = MessageTemplate::TRIGGER_MAP[MessageTemplate::TRIGGER_THANK_YOU];
            $thankYouTextTemplate->content = " ";
            $thankYouTextTemplate->save();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function fundraisers()
    {
        return $this->hasMany(Fundraiser::class);
    }

    public function elements()
    {
        return $this->hasMany(Element::class);
    }

    public function parentCampaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function childCampaigns()
    {
        return $this->hasMany(Campaign::class, 'campaign_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }


    public function donations()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getFamilyIds()
    {
        return $this->collectCampaignIds($this);
    }

    function collectCampaignIds($campaign)
    {
        $ids = [$campaign->id];
        foreach ($campaign->childCampaigns as $child) {
            $ids = array_merge($ids, $this->collectCampaignIds($child));
        }
        return $ids;
    }

    public function donationsWithDescendants()
    {
        return Transaction::whereIn('campaign_id', $this->getFamilyIds());
    }

    public function donors()
    {
        return $this->belongsToMany(Donor::class, Transaction::class, null, 'owner_id')->wherePivot('owner_type', 'donor');
    }

    public function donorsWithDescendants()
    {
        $donorIds = Transaction::whereIn('campaign_id', $this->getFamilyIds())->where('owner_type', 'donor')->pluck('owner_id')->all();

        return Donor::whereIn('id', $donorIds);
    }

    public function messageTemplates()
    {
        $elements = $this->elements->pluck('id')->all();
        return MessageTemplate::where('owner_type', 'element')->whereIn('owner_id', $elements)->orWhere([['owner_type', 'campaign'], ['owner_id', $this->id]]);
    }

    public function getNetDonationVolumeAttribute()
    {
        $donations = $this->donationsWithDescendants()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PENDING])->get();

        return $donations->sum('amount') - $donations->sum('fee');
    }

    public function getGrossDonationVolumeAttribute()
    {
        $donations = $this->donationsWithDescendants()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PENDING])->get();

        return $donations->sum('amount');
    }

    public function getDonationCountAttribute()
    {
        $donations = $this->donationsWithDescendants()->get();

        return count($donations);
    }

    public function getDonorCountAttribute()
    {
        $donors = $this->donorsWithDescendants()->get();

        return count($donors);
    }

    public function getRecurringDonationVolumeAttribute()
    {
        $donations = $this->donationsWithDescendants()->whereNotNull('scheduled_donation_id')->get();

        return $donations->sum('amount') - $donations->sum('fee');
    }

    public function getOneTimeDonationVolumeAttribute()
    {
        $donations = $this->donationsWithDescendants()->whereNull('scheduled_donation_id')->get();

        return $donations->sum('amount') - $donations->sum('fee');
    }

    public function leaderBoard()
    {

        $donors = $this->donorsWithDescendants()->get();

        foreach ($donors as &$donor) {

            $donations = $donor->donations()->whereIn('campaign_id', $this->getFamilyIds());
            $donor['given'] = $donations->sum('amount') - $donations->sum('fee');
        }

        $sorted =  $donors->sortByDesc(function ($item) {
            return $item['given'];
        })->values();


        return $sorted->take(15);
    }

    public function recentDonations()
    {

        return $this->donationsWithDescendants()->orderByDesc('created_at')->limit(15)->get();
    }
}
