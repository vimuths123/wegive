<?php

namespace App\Nova;


use Laravel\Nova\Fields\ID;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\MorphMany;
use Vyuldashev\NovaMoneyField\Money;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\HasManyThrough;
use Laravel\Nova\Http\Requests\NovaRequest;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Laravel\Nova\Fields\Slug;

class Organization extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Organization::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'legal_name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'ein', 'dba', 'legal_name'
    ];

    /**
     * Determine if the given resource is authorizable.
     *
     * @return bool
     */
    public static function authorizable()
    {
        return false;
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make('Legal Name')->required()->rules('required', 'max:191'),
            Slug::make('Slug')->from('legal_name')->required()->rules('required', 'max:191'),
            Text::make('Dba')->required()->rules('required', 'max:191'),
            Text::make('Address')->nullable()->rules('nullable', 'max:191'),
            Text::make('City')->nullable()->rules('nullable', 'max:191'),
            Text::make('State')->nullable()->rules('nullable', 'max:191'),
            Textarea::make('Mission Statement')->required(),
            Money::make('Total Revenue', 'USD')->storedInMinorUnits()->required(),
            Money::make('Total Expenses', 'USD')->required(),
            Money::make('Program Expense', 'USD')->required(),
            Money::make('Fundraising Expense', 'USD')->required(),
            Text::make('Tl Token'),
            Text::make('Code'),

            Text::make('EIN')->required()->rules('required', 'max:191'),
            BelongsToMany::make('Categories'),
            HasManyThrough::make('Posts'),
            HasMany::make('Funds'),
            HasMany::make('Payouts'),
            Images::make('Avatar'),
            Images::make('Banner'),
            Images::make('Thumbnail'),
            BelongsToMany::make('Givelists', 'givelists', 'App\Nova\Givelist')->searchable(),
            BelongsToMany::make('Product Codes', 'productCodes', 'App\Nova\ProductCode')->searchable(),
            Date::make('Onboarded'),

            HasMany::make("Transactions", 'receivedTransactions', 'App\Nova\Transaction'),
            MorphMany::make("Scheduled Donations", 'scheduledDonations', 'App\Nova\ScheduledDonation'),
            MorphMany::make('Fundraisers'),
            MorphMany::make('Checkouts'),
            Boolean::make('Visible')
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
