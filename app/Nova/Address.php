<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

class Address extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Address::class;

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
            Text::make('Address 1')->required()->rules('required', 'max:191'),
            Text::make('Address 2')->nullable()->rules('nullable', 'max:191'),
            Text::make('City')->required()->rules('required', 'max:191'),
            Text::make('State')->required()->rules('required', 'max:191'),
            Text::make('Zip')->required()->rules('required', 'max:191'),
            Number::make('neon_id')->nullable()->rules('nullable', 'digits:11'),
            Text::make('salesforce_id')->nullable()->rules('nullable', 'max:191'),
            MorphTo::make('Addressable')->types([
                Donor::class,
                // Company::class
            ])->required()->searchable()->rules('required'),
            Select::make('Type')->options([
                'billing' => 'Billing', 'mailing' => 'Mailing'
            ])->displayUsingLabels(),
            Boolean::make('Primary'),
            Text::make('type')->required()->rules('required', 'max:191'),
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
