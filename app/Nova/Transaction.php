<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Vyuldashev\NovaMoneyField\Money;

class Transaction extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Transaction::class;

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
            Text::make('Correlation Id')->nullable()->rules('nullable', 'max:191'),
            MorphTo::make('Source')->types([
                User::class,
                CardAccount::class,
                BankAccount::class,
                Organization::class,
                // Company::class
            ])->required()->searchable(),
            MorphTo::make('Owner')->types([
                User::class,
                // Company::class
            ])->required()->searchable(),
            MorphTo::make('Destination')->types([
                User::class,
                Organization::class,
                Givelist::class
            ])->required()->searchable(),
            Boolean::make('Type'),
            Boolean::make('Status'),
            BelongsTo::make('Scheduled Donation', 'scheduledDonation', 'App\Nova\ScheduledDonation')->searchable()->nullable(),
            BelongsTo::make('User')->searchable(),
            BelongsTo::make('Payout')->searchable()->nullable(),
            Money::make('Amount', 'USD')->storedInMinorUnits()->required(),
            Textarea::make('Description')->required()->rules('required'),
            Money::make('Fee', 'USD')->storedInMinorUnits()->required(),
            BelongsTo::make('Givelist')->searchable()->nullable(),
            BelongsTo::make('Campaign')->searchable()->nullable()->rules('nullable')->withoutTrashed(),
            BelongsTo::make('Element')->searchable()->nullable()->rules('nullable')->withoutTrashed(),
            BelongsTo::make('Fund')->searchable()->nullable(),
            BelongsTo::make('Fundraiser')->searchable()->nullable(),
            Text::make('Pf Id')->nullable()->rules('nullable', 'max:191'),
            Boolean::make('Guest'),
            Boolean::make('Direct Deposit'),
            Money::make('Fee Amount', 'USD')->storedInMinorUnits()->required(),
            Number::make('Scheduled Donation Iteration')->rules('digits:11', 'required'),
            Boolean::make('Anonymous'),
            Text::make('Tribute Email')->nullable()->rules('nullable', 'max:191'),
            Text::make('Tribute Name')->nullable()->rules('nullable', 'max:191'),
            Textarea::make('Tribute Message')->nullable(),
            Boolean::make('Tribute'),
            Text::make('Neon Id')->nullable()->rules('nullable', 'max:191'),
            Number::make('Neon Payment Id')->rules('digits:11', 'required'),
            Number::make('Lgl Id')->rules('digits:11', 'required'),
            Text::make('salesforce_id')->nullable()->rules('nullable', 'max:191'),
            Text::make('salesforce_payment_id')->nullable()->rules('nullable', 'max:191'),
            Text::make('salesforce_soft_credit_id')->nullable()->rules('nullable', 'max:191'),
            Text::make('salesforce_allocation_id')->nullable()->rules('nullable', 'max:191'),
            Number::make('Dp Id')->rules('digits:11', 'required'),
            
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
