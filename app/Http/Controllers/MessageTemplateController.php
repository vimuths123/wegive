<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageTemplateViewResource;
use App\Models\CustomEmailAddress;
use App\Models\Donor;
use App\Models\Element;
use App\Models\EmailTemplate;
use App\Models\MessageTemplate;
use App\Models\Organization;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function createTemplate(Request $request)
    {

        $request->validate([
            'content' => 'required',
            'trigger' => 'required',
            'type' => 'required'
        ]);

        $template = $this->initializeTemplate($request);
        $template->save();


        if ($request->donors && $template->trigger === MessageTemplate::TRIGGER_MANUALLY_SENT) {
            foreach ($request->donors as $donor) {
                $donorProfile = Donor::find($donor['id']);

                if ($donorProfile && $donorProfile->organization()->is(auth()->user()->currentLogin)) {
                    $logins = $donorProfile->logins;
                    $user = null;

                    if (count($logins) === 1) {
                        $user = $logins->first()->user;
                    }

                    if (!$user) continue;
                    $template->send($donorProfile, $user);
                }
            }
        }

        return $template;
    }

    public function getTemplate(Request $request, MessageTemplate $template)
    {

        if ($template->owner instanceof Element) {
            abort_unless($template->owner->campaign->organization()->is(auth()->user()->currentLogin), 401, 'Unauthorized');
        } else if ($template->owner instanceof Organization) {
            abort_unless($template->owner()->is(auth()->user()->currentLogin), 401, 'Unauthorized');
        }
        return new MessageTemplateViewResource($template);
    }

    public function getTemplates(Request $request)
    {
        $org = auth()->user()->currentLogin;
        $campaigns = $org->campaigns->pluck('id');
        $elements = Element::whereIn('campaign_id', $campaigns)->get()->pluck('id');
        $templates = MessageTemplate::where('owner_type', 'element')->whereIn('owner_id', $elements);


        if ($request->trigger) {

            $templates = $templates->where('trigger', $request->trigger);
        } else {
            $templates = $templates->where('trigger', '!=', MessageTemplate::TRIGGER_MANUALLY_SENT);
        }

        $templates = $templates->orWhere([['owner_type', 'organization'], ['owner_id', $org->id]]);


        if ($request->trigger) {

            $templates = $templates->where('trigger', $request->trigger);
        } else {
            $templates = $templates->where('trigger', '!=', MessageTemplate::TRIGGER_MANUALLY_SENT);
        }

        return MessageTemplateViewResource::collection($templates->get());
    }

    public function updateTemplate(Request $request, MessageTemplate $template)
    {


        if ($template->owner instanceof Element) {
            abort_unless($template->owner->campaign->organization()->is(auth()->user()->currentLogin), 401, 'Unauthorized');
        } else if ($template->owner instanceof Organization) {
            abort_unless($template->owner()->is(auth()->user()->currentLogin), 401, 'Unauthorized');
        }



        $template->update($request->only(['content', 'trigger', 'email', 'enabled', 'subject']));



        if ($request->email_template_id) {
            $emailTemplate = EmailTemplate::find($request->email_template_id);
            $organization = null;

            if ($template->owner_type === 'organization') {
                $organization = $template->owner;
            } else if ($template->owner_type === 'element') {
                $organization = $template->owner->campaign->organization;
            }
            if ($emailTemplate->organization()->is($organization)) {
                $template->emailTemplate()->associate($emailTemplate);
            }
        } else if ($request->has('email_template_id')) {
            $template->emailTemplate()->associate(null);
        }

        if ($request->custom_email_address_id) {
            $address = CustomEmailAddress::find($request->custom_email_address_id);
            $customEmailDomain = $address->domain;
            if ($customEmailDomain->organization()->is(auth()->user()->currentLogin)) {
                $template->customEmailDomain()->associate($customEmailDomain);
                $template->customEmailAddress()->associate($address);
            }
        } else if ($request->has('custom_email_address_id') && $request->custom_email_address_id == null) {
            $template->customEmailDomain()->associate(null);
            $template->customEmailAddress()->associate(null);
        }
        $template->save();

        return $template;
    }

    public function deleteTemplate(Request $request, MessageTemplate $template)
    {

        if ($template->owner instanceof Element) {
            abort_unless($template->owner->campaign->organization()->is(auth()->user()->currentLogin), 401, 'Unauthorized');
        } else if ($template->owner instanceof Organization) {
            abort_unless($template->owner()->is(auth()->user()->currentLogin), 401, 'Unauthorized');
        }

        $template->delete();

        return $template;
    }

    public function sendTestTemplate(Request $request, MessageTemplate $template)
    {

        if ($template->owner instanceof Element) {
            abort_unless($template->owner->campaign->organization()->is(auth()->user()->currentLogin), 401, 'Unauthorized');
        } else if ($template->owner instanceof Organization) {
            abort_unless($template->owner()->is(auth()->user()->currentLogin), 401, 'Unauthorized');
        }

        $transaction = auth()->user()->currentLogin->receivedTransactions()->latest()->first();

        $template->send($transaction, auth()->user());

        return;
    }

    public function sendPreviewTemplate(Request $request)
    {
        $template = $this->initializeTemplate($request);
        $transaction = auth()->user()->currentLogin->receivedTransactions()->latest()->first();
        $template->send($transaction, auth()->user());
    }


    /**
     * Creates but doesn't commit to the DB
     */
    private function initializeTemplate(Request $request)
    {

        $owner = auth()->user()->currentLogin;

        if ($request->element) {
            $element = Element::find($request->element);

            if ($element->campaign->organization()->is($owner)) {
                $owner = $element;
            }
        } else {
            $owner = auth()->user()->currentLogin;
        }

        $template = new MessageTemplate();

        $template->owner()->associate($owner);
        $template->trigger = $request->trigger;
        $template->content = $request->content;
        $template->type = $request->type;
        $template->enabled = true;
        if ($request->email_template_id) {
            $emailTemplate = EmailTemplate::find($request->email_template_id);
            $organization = null;

            if ($template->owner_type === 'organization') {
                $organization = $template->owner;
            } else if ($template->owner_type === 'element') {
                $organization = $template->owner->campaign->organization;
            }
            if ($emailTemplate->organization()->is($organization)) {
                $template->emailTemplate()->associate($emailTemplate);
            }
        }

        if ($request->custom_email_address_id) {
            $address = CustomEmailAddress::find($request->custom_email_address_id);
            $customEmailDomain = $address->domain;
            if ($customEmailDomain->organization()->is(auth()->user()->currentLogin)) {
                $template->customEmailDomain()->associate($customEmailDomain);
                $template->customEmailAddress()->associate($address);
            }
        } else if ($request->has('custom_email_address_id') && $request->custom_email_address_id == null) {
            $template->customEmailDomain()->associate(null);
            $template->customEmailAddress()->associate(null);
        }

        return $template;
    }
}
