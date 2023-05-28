<?php

namespace App\Processors;

use Mailgun\Mailgun;
use App\Models\CustomEmailAddress;
use App\Models\CustomEmailDomain;
use Mailgun\Hydrator\ArrayHydrator;
use Illuminate\Support\Facades\Http;
use Mailgun\HttpClient\HttpClientConfigurator;

class MailgunHelper
{
    private CustomEmailAddress $address;

    private $mg;

    public function __construct(CustomEmailAddress $address = null)
    {

        if (!$address) {
            $address = new CustomEmailAddress();
            $address->handle = 'support';
            $address->display_name = 'WeGive';

            $domain = new CustomEmailDomain();
            $domain->domain = config('services.mailgun.domain');

            $address->domain()->associate($domain);
        }

        $this->address = $address;

        $configurator = new HttpClientConfigurator();
        $configurator->setApiKey(config('services.mailgun.secret'));

        $this->mg = new Mailgun($configurator, new ArrayHydrator());
    }

    public function isVerified(CustomEmailDomain $domain = null)
    {

        if ($domain) {
            $data = $this->mg->domains()->show($domain->domain);

            return $data['domain']['state'] === 'active';
        }

        $data = $this->mg->domains()->show($this->address->domain->domain);

        return $data['domain']['state'] === 'active';
    }

    public function getDomain(CustomEmailDomain $domain = null)
    {
        if ($domain) {
            $data = $this->mg->domains()->show($domain->domain);
            $data['domain']['smtp_password'] = null;

            return $data;
        }

        $data = $this->mg->domains()->show($this->address->domain->domain);
        $data['domain']['smtp_password'] = null;

        return $data;
    }


    public function createDomain(CustomEmailDomain $domain = null)
    {

        if ($domain) {
            $data = $this->mg->domains()->create($domain->domain);
            $data['domain']['smtp_password'] = null;

            return $data;
        }

        $data = $this->mg->domains()->create($this->address->domain->domain);
        $data['domain']['smtp_password'] = null;

        return $data;
    }


    public function sendEmail($html = null, $to = null, $subject = null)
    {

        if (!$this->isVerified()) {
            $this->address = new CustomEmailAddress();
            $this->address->handle = 'support';
            $this->address->display_name = 'WeGive';

            $domain = new CustomEmailDomain();
            $domain->domain = config('services.mailgun.domain');

            $this->address->domain()->associate($domain);
        }


        return $this->mg->messages()->send($this->address->domain->domain, ['from' => " {$this->address->display_name} <{$this->address->handle}@{$this->address->domain->domain}>", 'to' => $to, 'html' => $html, 'subject' => $subject]);
    }
}
