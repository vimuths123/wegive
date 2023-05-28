<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class SalesforceIntegration extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\SalesforceIntegration::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'username';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'username', 'client_id'
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
            BelongsTo::make('Organization')->searchable()->required()->rules('required'),
            Boolean::make('Crm Sync'),
            Boolean::make('Two Way Cync'),
            Boolean::make('Track Donations'),
            Boolean::make('Track Accounts'),
            Boolean::make('Track Donors'),
            Boolean::make('Track Recurring Donations'),
            Boolean::make('Track Campaigns'),
            Boolean::make('Track Designations'),
            Boolean::make('Enabled'),
            Text::make('Client Id')->nullable()->rules('nullable', 'max:191'),
            Text::make('Client Secret')->nullable()->rules('nullable', 'max:191'),
            Text::make('Username')->nullable()->rules('nullable', 'max:191'),
            Text::make('Password')->nullable()->rules('nullable', 'max:191'),
            Text::make('Instance Url')->nullable()->rules('nullable', 'max:191'),
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
