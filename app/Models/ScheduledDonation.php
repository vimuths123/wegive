<?php

namespace App\Models;

use App\Jobs\ProcessDonorPerfectScheduledDonations;
use DateTime;
use App\Models\Organization;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Jobs\ProcessNeonIntegrationRecurringDonations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Jobs\ProcessSalesforceRecurringDonationIntegration;

class ScheduledDonation extends Model implements auditable
{
    use HasFactory;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    public const DONATION_FREQUENCY_MONTHLY = 1;
    public const DONATION_FREQUENCY_WEEKLY = 2;
    public const DONATION_FREQUENCY_BIMONTHLY = 3;
    public const DONATION_FREQUENCY_YEARLY = 4;
    public const DONATION_FREQUENCY_DAILY = 5;
    public const DONATION_FREQUENCY_QUARTERLY = 6;


    public const PAUSE_TYPE_INDEFINITE = 1;
    public const PAUSE_TYPE_1_MONTH = 2;
    public const PAUSE_TYPE_3_MONTH = 3;
    public const PAUSE_TYPE_6_MONTH = 4;

    public const PAUSE_TYPE_TO_MONTHS = [null, null, 1, 3, 6];


    public const DONATION_FREQUENCY_MAP = [null, 'monthly', 'weekly', 'bimonthly', 'yearly', 'daily', 'quarterly'];

    public const DONATION_FREQUENCY_TO_INT = ['monthly' => 1, 'weekly' => 2, 'bimonthly' => 3, 'yearly' => 4, 'daily' => 5, 'quarterly' => 6];



    protected $fillable = ['destination_id', 'destination_type', 'amount', 'locked', 'paused_at', 'campaign_id'];


    public const SORT_MAP = [
        'last_processed' => 'updated_at',
        'amount' => 'amount',
        'fee_amount' => 'fee_amount',
        'donor' => null,
        'designation' => null
    ];


    protected $casts = [
        'locked' => 'boolean',
        'start_date' => 'date',
    ];


    public static function boot()
    {
        parent::boot();

        self::updated(function ($model) {
            if ($model->destination instanceof Organization) {
                if ($n = $model->destination->neonIntegration) {
                    ProcessNeonIntegrationRecurringDonations::dispatch($n, $model);
                }
                $s = $model->destination->salesforceIntegration;
                if ($s && $s->enabled) {
                    ProcessSalesforceRecurringDonationIntegration::dispatch($s, $model);
                }

                $dp = $model->destination->donorPerfectIntegration;
                if ($dp && $dp->enabled) {
                    ProcessDonorPerfectScheduledDonations::dispatch($dp, $model);
                }
            }
        });

        self::created(function ($model) {
            if ($model->destination instanceof Organization) {
                if ($n = $model->destination->neonIntegration) {
                    ProcessNeonIntegrationRecurringDonations::dispatch($n, $model);
                }
                $s = $model->destination->salesforceIntegration;
                if ($s && $s->enabled) {
                    if (!$model->salesforce_id) {
                        ProcessSalesforceRecurringDonationIntegration::dispatch($s, $model);
                    }
                }

                $dp = $model->destination->donorPerfectIntegration;
                if ($dp && $dp->enabled) {
                    ProcessDonorPerfectScheduledDonations::dispatch($dp, $model);
                }
            }
            $currentLogin = auth()->user()->currentLogin;
            activity()->causedBy($currentLogin)->performedOn($model->destination)->log("Set a recurring gift for {$model->destination->name}");

            $amount = $model->amount;
            if ($model->cover_fees) {
                $amount += $model->fee_amount;
            }

            $amount += $model->tip;


            $transaction = $model->destination->give($model->paymentMethod, $amount, 'Recurring Donation', $model->id, $model->tip, null, $model->fundraiser_id, null, false, $model->cover_fees, $model->fee_amount, null, true, null, $model->campaign_id);
            if ($model->user) {
                $transaction->user()->associate($model->user);
            }
            if ($model->tribute) {

                $transaction->tribute = true;
                $transaction->tribute_name = $model->tribute_name;
                $transaction->tribute_message = $model->tribute_message;
                $transaction->tribute_email = $model->tribute_email;
                $transaction->saveQuietly();

                if ($model->tribute_email) {
                    Mail::send('emails.tribute', ['tributeName' => $model->tribute_name, 'donorName' => $transaction->owner->name(), 'destinationName' => $transaction->destination->name, 'tributeMessage' => $model->tribute_message, 'logo' => $transaction->destination->getFirstMedia('avatar') ?  $transaction->destination->getFirstMedia('avatar')->getUrl() : null], function ($message) use ($model) {
                        $message->to($model->tribute_email)
                            ->subject('Someone has donated in your honor');
                    });
                }
            }

            $transaction->fee = $model->tip;
            $transaction->anonymous = false;
            $transaction->scheduled_donation_iteration = $model->iteration + 1;
            $transaction->cover_fees = $model->cover_fees;
            $transaction->fee_amount = $model->fee_amount;
            $transaction->campaign_id = $model->campaign_id;
            $transaction->fundraiser_id = $model->fundraiser_id;
            $transaction->save();
            $model->iteration += 1;
            $model->saveQuietly();
            $startDate = new DateTime($model->start_date);
            $modifiedDate = null;


            if ($model->frequency == ScheduledDonation::DONATION_FREQUENCY_MONTHLY) {

                $modifiedDate = $startDate->modify('+ 1 month');
            }
            if ($model->frequency === ScheduledDonation::DONATION_FREQUENCY_WEEKLY) {
                $modifiedDate = $startDate->modify('+ 1 week');
            }

            if ($model->frequency === ScheduledDonation::DONATION_FREQUENCY_BIMONTHLY) {
                $modifiedDate = $startDate->modify('+ 15 days');
            }

            if ($model->frequency === ScheduledDonation::DONATION_FREQUENCY_DAILY) {
                $modifiedDate = $startDate->modify('+ 1 days');
            }

            if ($model->frequency === ScheduledDonation::DONATION_FREQUENCY_QUARTERLY) {
                $modifiedDate = $startDate->modify('+ 90 days');
            }


            if ($model->frequency === ScheduledDonation::DONATION_FREQUENCY_YEARLY) {
                $modifiedDate = $startDate->modify('+ 1 year');
            }

            $model->start_date = $modifiedDate;
            $model->saveQuietly();
        });

        self::deleted(function ($model) {
            $currentLogin = auth()->user()->currentLogin;

            activity()->causedBy($currentLogin)->performedOn($model->destination)->log("Removed recurring gift for {$model->destination->name}'s.");
        });
    }

    public function getChargeAmountAttribute()
    {

        $amount = $this->amount + $this->tip;

        if ($this->cover_fees) $amount += $this->fee_amount;

        return $amount;
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function paymentMethod()
    {
        return $this->morphTo();
    }

    public function destination()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function fundraiser()
    {
        return $this->belongsTo(Fundraiser::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function lastDateProcessed()
    {
        $transaction = $this->transactions()->orderBy('id', 'desc')->first();
        if ($transaction) return $transaction->created_at;
        return null;
    }
}
