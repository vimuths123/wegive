<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;

use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Requests\NovaRequest;

class BankAccount extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Bank::class;

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
            Text::make('Last Four')->rules('required'),
            Text::make('Name')->rules('required'),
            Text::make('TL Token', 'tl_token')->rules('required'),
            Text::make('Vgs Routing Number Token'),
            Text::make('Vgs Account Number Token'),
            HasMany::make("Transactions", 'sentTransactions', 'App\Nova\Transaction'),
            HasMany::make("Scheduled Donations"),
            Boolean::make('Primary'),    
            DateTime::make('User Agreed'),    
            Text::make('Pf Token')->nullable()->rules('nullable', 'max:191'),
            Text::make('Pf Id')->nullable()->rules('nullable', 'max:191'), 
            Text::make('Stripe Id')->nullable()->rules('nullable', 'max:191'),    
            Text::make('Plaid Token')->nullable()->rules('nullable', 'max:191'),  
            Text::make('Plaid Account Id')->nullable()->rules('nullable', 'max:191'),  
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
