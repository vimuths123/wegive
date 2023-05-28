<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;

class CardAccount extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Card::class;

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
            MorphTo::make('Owner')->types([
                User::class,
                // Company::class,
            ])->nullable(),
            Text::make('Name')->required(),
            Text::make('Issuer')->required(),
            Text::make('Pf Token')->nullable()->rules('nullable', 'max:191'),
            Boolean::make('Primary'),    
            Text::make('Vgs Number Token'),
            Text::make('Vgs Security code Token'),
            Text::make('Zip Code'),
            Text::make('Last Four')->required(),
            Text::make('Expiration')->required(),
            Text::make('TL Token', 'tl_token')->required(),
            Text::make('Vgs Expiration Year Token')->nullable()->rules('nullable', 'max:191'),
            Text::make('Vgs Expiration Month Token')->nullable()->rules('nullable', 'max:191'),
            HasMany::make("Transactions", 'sentTransactions', 'App\Nova\Transaction'),
            HasMany::make("Scheduled Donations"),
            DateTime::make('Expires At')->nullable(),  
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
     * @param  \Ill+uminate\Http\Request  $request
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
