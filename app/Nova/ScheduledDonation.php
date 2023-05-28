<?php

namespace App\Nova;

use App\Models\Bank;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Card;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\MorphTo;

use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\BelongsTo;
use Vyuldashev\NovaMoneyField\Money;
use Laravel\Nova\Http\Requests\NovaRequest;

class ScheduledDonation extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\ScheduledDonation::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

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
            MorphTo::make('Source')->types([
                User::class,
                // Company::class,
            ])->searchable(),
            MorphTo::make('Destination')->types([
                Organization::class,
                Givelist::class,
                User::class,
            ])->searchable(),
            MorphTo::make('payment Method', 'paymentMethod')->types([
                BankAccount::class,
                CardAccount::class,
            ])->searchable(),
            Money::make('Amount', 'USD')->storedInMinorUnits()->required(),
            Money::make('Tip', 'USD')->storedInMinorUnits()->required(),
            Money::make('Fee Amount', 'USD')->storedInMinorUnits()->required(),
            Boolean::make('Cover Fees'),
            Date::make('Created At'),
            Date::make('Updated At'),
            Date::make('Start Date'),
            BelongsTo::make('Campaign')->searchable()->nullable()->withoutTrashed(),
            BelongsTo::make('Fundraiser')->searchable()->nullable()->withoutTrashed(),    
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
