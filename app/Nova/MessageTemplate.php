<?php

namespace App\Nova;

use App\Models\MessageTemplate as ModelsMessageTemplate;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class MessageTemplate extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\MessageTemplate::class;

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
            ])->required()->rules('required'),
            Select::make('Type')->options([
                ModelsMessageTemplate::TRIGGER_RECEIPT => 'Receipt', 
                ModelsMessageTemplate::TRIGGER_THANK_YOU => 'Thank You',
                ModelsMessageTemplate::TRIGGER_RECURRING_DONATION_CREATED => 'Recurring Donation Created', 
                ModelsMessageTemplate::TRIGGER_DONATION_FAILED => 'Donation Failed',
                ModelsMessageTemplate::TRIGGER_TRIBUTE_MADE => 'Tribute made', 
                ModelsMessageTemplate::TRIGGER_MANUALLY_SENT => 'Manually Sent',
            ])->displayUsingLabels()->required()->rules('required'),
            Textarea::make('Content')->required()->rules('required'),
            Boolean::make('Trigger'),
            Boolean::make('Enabled'),
            Textarea::make('Subject')->nullable(),
            BelongsTo::make('Email Template', 'emailTemplate')->searchable()->nullable(),
            BelongsTo::make('Custom Email Domain', 'customEmailDomain')->searchable()->nullable(),
            BelongsTo::make('Custom Email Address', 'customEmailAddress')->searchable()->nullable(),
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
