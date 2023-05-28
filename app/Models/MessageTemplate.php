<?php

namespace App\Models;

use Exception;
use Rollbar\Rollbar;
use Twilio\Rest\Client;
use App\Nova\Individual;
use Rollbar\Payload\Level;
use Soundasleep\Html2Text;
use App\Processors\MailgunHelper;
use Illuminate\Database\Eloquent\Model;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TRIGGER_RECEIPT = 1;
    public const TRIGGER_THANK_YOU = 2;
    public const TRIGGER_RECURRING_DONATION_CREATED = 3;
    public const TRIGGER_DONATION_FAILED = 4;
    public const TRIGGER_TRIBUTE_MADE = 5;
    public const TRIGGER_MANUALLY_SENT = 6;

    public const TYPE_TEXT = 1;
    public const TYPE_EMAIL = 2;


    protected $fillable = ['content', 'trigger', 'enabled', 'subject', 'email_template_id'];


    public const TRIGGER_MAP = [null, 'Donation Receipt', 'Donation Thank You', 'Recurring Donation Setup', 'Donation Failed', 'Tribute Made', 'Manually Sent'];

    public const TYPE_MAP = [null, 'Text', 'Email'];


    public const TRIGGER_SUBJECT_MAP = [null, 'Donation Receipt', 'Thank you for your donation', 'Your recurring giving has been set', 'Your donation has failed', 'Someone has tributed their donation to you', 'You have been sent an email'];

    public function communications()
    {
        return $this->morphMany(Communication::class, 'message');
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function customEmailDomain()
    {
        return $this->belongsTo(CustomEmailDomain::class);
    }

    public function customEmailAddress()
    {
        return $this->belongsTo(CustomEmailAddress::class);
    }


    public function owner()
    {
        return $this->morphTo();
    }

    public function totalSent()
    {
        return count($this->communications);
    }

    public function send($model = null, $overrideUser = null)
    {
        if (app()->environment('local')) return;
        if ($this->type === MessageTemplate::TYPE_TEXT) {
            $this->sendText($model, $overrideUser);
        }

        if ($this->type === MessageTemplate::TYPE_EMAIL) {
            $this->sendEmail($model, $overrideUser);
        }
    }

    public function sendText($model = null, $overrideUser = null)
    {
        $content = $this->content;



        if ($model instanceof Transaction) {

            $user = $overrideUser ?? $model->user;

            $content = str_replace(Transaction::SEARCH_ARRAY, $model->replaceArray(), $content);



            if (strlen($content) <= 1) return;

            $client = new Client(config('services.twilio.account'), config('services.twilio.token'));



            if (!$user || !$user->phone) return;

            try {
                $client->messages->create($user->phone, [
                    'from' => config('services.twilio.number'),
                    'body' => Html2Text::convert($content)
                ]);


                $communication = new Communication();

                $communication->sender()->associate($model->destination);
                $communication->initiator()->associate($this->owner);
                $communication->receiver()->associate($model->owner);
                $communication->subject()->associate($model);
                $communication->message()->associate($this);
                $communication->content = $content;
                $communication->status = Communication::STATUS_SENT;
                $communication->communication_type = Communication::TYPE_SMS;
                $communication->save();
            } catch (Exception $e) {

                Bugsnag::notifyException($e);
            }
        }

        if ($model instanceof Donor) {
            $user = $overrideUser ?? $model->user;

            $content = str_replace(Donor::SEARCH_ARRAY, $model->replaceArray(), $content);

            if (strlen($content) <= 1) return;

            $client = new Client(config('services.twilio.account'), config('services.twilio.token'));

            if (!$user || !$user->phone) return;

            try {
                $client->messages->create($user->phone, [
                    'from' => config('services.twilio.number'),
                    'body' => Html2Text::convert($content)
                ]);

                $communication = new Communication();

                $communication->sender()->associate($model->organization);
                $communication->initiator()->associate($this->owner);
                $communication->receiver()->associate($model->owner);
                $communication->subject()->associate($model);
                $communication->message()->associate($this);
                $communication->content = $content;
                $communication->status = Communication::STATUS_SENT;
                $communication->communication_type = Communication::TYPE_SMS;
                $communication->save();
            } catch (Exception $e) {

                Bugsnag::notifyException($e);
            }
        }


        return;
    }

    public function sendEmail($model = null, $overrideUser = null)
    {
        $content = $this->content;
        $subject = $this->subject;


        if ($model instanceof Transaction) {
            $content = str_replace(Transaction::SEARCH_ARRAY, $model->replaceArray(), $content);
            if (strlen($content) <= 1) return;

            $subject = strip_tags(str_replace(Transaction::SEARCH_ARRAY, $model->replaceArray(), $subject));

            $user = $overrideUser ??  $model->user;

            if (!$user || !$user->email) return;

            $email = $user->email;

            try {

                $org = $model->destination;
                if ($this->emailTemplate) {

                    $template = str_replace('{{ content }}', $content, $this->emailTemplate->template);


                    $mg = new MailgunHelper($this->customEmailAddress);
                    $mg->sendEmail(view('emails.customtemplate', ['emailTemplate' => $template])->render(), $email, $subject);
                } else {


                    $mg = new MailgunHelper($this->customEmailAddress);
                    $mg->sendEmail(view('emails.template', ['content' => $content])->render(), $email, $subject);
                }


                $communication = new Communication();

                $communication->sender()->associate($model->destination);
                $communication->initiator()->associate($this->owner);
                $communication->receiver()->associate($model->owner);
                $communication->subject()->associate($model);
                $communication->content = $content;
                $communication->message()->associate($this);
                $communication->status = Communication::STATUS_SENT;
                $communication->communication_type = Communication::TYPE_EMAIL;
                $communication->save();
            } catch (Exception $e) {

                Bugsnag::notifyException($e);
            }
        }


        if ($model instanceof Donor) {
            $content = str_replace(Donor::SEARCH_ARRAY, $model->replaceArray(), $content);

            if (strlen($content) <= 1) return;

            $subject = strip_tags(str_replace(Donor::SEARCH_ARRAY, $model->replaceArray(), $subject));

            $user = $overrideUser ??  $model->user;

            if (!$user || !$user->email) return;

            $email = $user->email;

            try {

                $org = $model->destination;

                if ($this->emailTemplate) {

                    $template = str_replace('{{ content }}', $content, $this->emailTemplate->template);


                    $mg = new MailgunHelper($this->customEmailAddress);
                    $mg->sendEmail(view('emails.customtemplate', ['emailTemplate' => $template])->render(), $email, $subject);
                } else {


                    $mg = new MailgunHelper($this->customEmailAddress);
                    $mg->sendEmail(view('emails.template', ['content' => $content])->render(), $email, $subject);
                }


                $communication = new Communication();

                $communication->sender()->associate($model->destination);
                $communication->initiator()->associate($this->owner);
                $communication->receiver()->associate($model->owner);
                $communication->subject()->associate($model);
                $communication->content = $content;
                $communication->message()->associate($this);
                $communication->status = Communication::STATUS_SENT;
                $communication->communication_type = Communication::TYPE_EMAIL;
                $communication->save();
            } catch (Exception $e) {

                Bugsnag::notifyException($e);
            }
        }



        return;
    }
}
