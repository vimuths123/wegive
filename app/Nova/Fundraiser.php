<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;

use Vyuldashev\NovaMoneyField\Money;
use Laravel\Nova\Http\Requests\NovaRequest;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Slug;

class Fundraiser extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Fundraiser::class;

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
            MorphTo::make('Owner')->types([User::class, Organization::class])->searchable()->required(),
            Text::make('Name')->required()->rules('required'),
            Textarea::make('Description')->required()->rules('required'),
            Money::make('Goal', 'USD')->storedInMinorUnits()->required()->rules('required'),
            Select::make('Publicity')->options([
                '1' => 'Private',
                '2' => 'Followers',
                '3' => 'Public',
            ])->displayUsingLabels()->required()->rules('required'),
            MorphTo::make('Recipient')->types([
                User::class,
                Organization::class,
                Givelist::class,
            ])->required()->searchable()->rules('required'),            
            Images::make('Thumbnail'),
            BelongsTo::make('Campaign')->searchable()->nullable()->withoutTrashed(),    
            DateTime::make('Expiration')->nullable(),   
            Textarea::make('Location')->nullable(),
            Text::make('Neon Id')->nullable()->rules('nullable', 'max:191'),
            Text::make('Salesforce Id')->nullable()->rules('nullable', 'max:191'),
            BelongsTo::make('Checkout')->searchable()->nullable()->withoutTrashed(),    
            Slug::make('Slug')->from('Name')->nullable(),
            Boolean::make('Show Leader Board'),
            Boolean::make('Show Activity'), 
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
