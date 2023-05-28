<?php

namespace App\Providers;

use App\Models\{Address,
    Bank,
    Campaign,
    Card,
    Category,
    Checkout,
    Comment,
    CustomQuestion,
    Donor,
    Element,
    Fund,
    Fundraiser,
    Givelist,
    Household,
    ImpactNumber,
    MessageTemplate,
    Organization,
    Post,
    SalesforceIntegration,
    ScheduledDonation,
    Transaction,
    User};
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const MORPH_MAP = [
        'comment'                => Comment::class,
        'category'               => Category::class,
        'givelist'               => Givelist::class,
        'organization'           => Organization::class,
        'post'                   => Post::class,
        'user'                   => User::class,
        'card'                   => Card::class,
        'bank'                   => Bank::class,
        'fundraiser'             => Fundraiser::class,
        'fund'                   => Fund::class,
        'donor'                  => Donor::class,
        'address'                => Address::class,
        'impact_number'          => ImpactNumber::class,
        'transaction'            => Transaction::class,
        'scheduled_donation'     => ScheduledDonation::class,
        'household'              => Household::class,
        'checkout'               => Checkout::class,
        'element'                => Element::class,
        'message_template'       => MessageTemplate::class,
        'custom_question'        => CustomQuestion::class,
        'campaign'               => Campaign::class,
        'salesforce_integration' => SalesforceIntegration::class,

    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap(self::MORPH_MAP);
    }
}
