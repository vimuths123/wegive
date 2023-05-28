<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Requests\NovaRequest;

class Checkout extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Checkout::class;

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
            MorphTo::make('Recipient')->types([Organization::class, Givelist::class]),
            Boolean::make('Informational Content'),
            Text::make('Headling'),
            Text::make('Description'),
            Text::make('Link 1 Name'),
            Text::make('Link 1'),
            Text::make('Link 2 Name'),
            Text::make('Link 2'),
            Text::make('Link 3 Name'),
            Text::make('Link 3'),
            Number::make('Suggested Amount 1'),
            Number::make('Suggested Amount 2'),
            Number::make('Suggested Amount 3'),
            Number::make('Suggested Amount 4'),
            Number::make('Suggested Amount 5'),
            Number::make('Suggested Amount 6'),
            Number::make('Recurring Suggested Amount 1'),
            Number::make('Recurring Suggested Amount 2'),
            Number::make('Recurring Suggested Amount 3'),
            Number::make('Recurring Suggested Amount 4'),
            Number::make('Recurring Suggested Amount 5'),
            Number::make('Recurring Suggested Amount 6'),
            Text::make('Suggested Amount 1 Description'),
            Text::make('Suggested Amount 2 Description'),
            Text::make('Suggested Amount 3 Description'),
            Text::make('Suggested Amount 4 Description'),
            Text::make('Suggested Amount 5 Description'),
            Text::make('Suggested Amount 6 Description'),
            Text::make('Recurring Suggested Amount 1 Description'),
            Text::make('Recurring Suggested Amount 2 Description'),
            Text::make('Recurring Suggested Amount 3 Description'),
            Text::make('Recurring Suggested Amount 4 Description'),
            Text::make('Recurring Suggested Amount 5 Description'),
            Text::make('Recurring Suggested Amount 6 Description'),
            BelongsTo::make('Impact Number', 'impactNumber'),
            Boolean::make('Conversion Step'),
            Number::make('Default Frequency'),
            Boolean::make('Allow Frequency Change'),
            Boolean::make('Designation'),
            Boolean::make('Tribute'),
            Boolean::make('Credit Card'),
            Boolean::make('Apple Pay'),
            Boolean::make('Google Pay'),
            Boolean::make('Ach'),
            Boolean::make('Bank Login'),
            Boolean::make('Crypto'),
            Boolean::make('Show Savings'),
            Boolean::make('Fee Pass'),
            Boolean::make('Default To Covered'),
            Boolean::make('Tipping'),
            Boolean::make('Donate Anonymously'),
            Boolean::make('Anonymous Donations'),
            Boolean::make('Require Verification'),
            Text::make('Thank You Headline'),
            Text::make('Thank You Description'),
            Number::make('Number Of Suggested Amounts'),
            Number::make('Recurring Number Of Suggested Amounts')
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
