<?php

namespace App\Nova;

use App\Models\Bank;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Card;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Donor extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Donor::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'type'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        $donorProfileType = $this->type;


        $model = 'App\Nova\Donor';

        // if ($donorProfileType === 'company') {

        //     $model = 'App\Nova\Company';
        // }


        return [
            ID::make(__('ID'), 'id')->sortable(),
            // Text::make('Handle'),
            Text::make('First Name'),
            Text::make('Last Name'),
            Text::make('Email 1')->rules('email', 'nullable'), 
            Text::make('Email 2')->rules('email', 'nullable'),  
            Text::make('Email 3')->rules('email', 'nullable'),  
            BelongsTo::make('Organization')->searchable()->required()->rules('required'),
            Number::make('Neon Account Id'),
            Number::make('Neon Id'),
            Text::make('Salesforce Id')->rules('nullable'), 
            Text::make('Salesforce Account Id')->rules('nullable'),  
            Select::make('Type')->options([
                'individual' => 'Individual',
                'company' => 'Company',
            ])->rules('required'),
            Text::make('Mobile Phone')->rules('nullable'), 
            Text::make('Name'),
            DateTime::make('Is Public')->rules('nullable'), 
            MorphTo::make('Preferred Payment', 'preferredPayment')->types([
                CardAccount::class,
                BankAccount::class
            ])->rules('nullable')->searchable()->nullable(),
            Text::make('Home Phone')->rules('nullable'), 
            Text::make('Office Phone')->rules('nullable'), 
            Text::make('Fax')->rules('nullable'), 
            Number::make('Dp Id'),
            BelongsToMany::make('Posts')->rules('nullable')->nullable(),
            BelongsToMany::make('Impact Numbers', 'impactNumbers')->rules('nullable')->nullable(),
            MorphMany::make("Scheduled Donations", 'scheduledDonations', 'App\Nova\ScheduledDonation'),
            MorphMany::make("Transactions", 'transactions', 'App\Nova\Transaction'),
            MorphMany::make("Fundraisers", 'fundraisers', 'App\Nova\Fundraiser'),
            MorphMany::make('Addresses'),
            Text::make('Birthdate')->nullable(),
            Textarea::make('Notes')->nullable(),
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
