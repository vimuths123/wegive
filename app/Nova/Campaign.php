<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Campaign extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Campaign::class;

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
        'name', 'goal'
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
            Text::make('Name')->rules('required'), 
            BelongsTo::make('Organization')->searchable()->required()->rules('required'),
            BelongsTo::make('Parent Campaign', 'parentCampaign', 'App\Nova\Campaign')->nullable()->withoutTrashed(),
            Text::make('goal')->rules('numeric', 'digits:11', 'nullable'), 
            Date::make('Start Date')->nullable(),
            Date::make('End Date')->nullable(),
            Text::make('Fundraiser Name')->nullable()->rules('nullable', 'max:191'),
            Text::make('Neon Id')->rules('numeric', 'digits:11', 'nullable'), 
            Text::make('Salesforce Id')->nullable()->rules('nullable', 'max:191'),
            Slug::make('Slug')->from('Name')->nullable()->rules('nullable', 'max:191'),
            Text::make('Type')->rules('numeric', 'digits:11', 'nullable'), 
            Textarea::make('Fundraiser Description')->nullable(),
            Boolean::make('Fundraiser Donations P2p Only'),
            Boolean::make('Fundraiser Show Leader Board'),
            Boolean::make('Fundraiser Show Activity'),
            Boolean::make('Fundraiser Show Child Fundraiser Campaign'),
            Boolean::make('Fundraiser Show Child Event Campaign'),
            BelongsTo::make('Checkout')->searchable()->nullable(),
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
