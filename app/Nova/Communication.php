<?php

namespace App\Nova;

use App\Models\Communication as ModelsCommunication;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Communication extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Communication::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'content';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'content',
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
            MorphTo::make('Subject', 'subject')->types([
                Transaction::class,
                Donor::class
            ])->searchable()->rules('required'),
            MorphTo::make('Sender', 'sender')->types([
                Organization::class,
                Givelist::class,
                User::class,
            ])->searchable()->rules('required'),
            MorphTo::make('Receiver', 'receiver')->types([
                Donor::class,
                BankAccount::class, 
                User::class,
            ])->searchable()->rules('required'),
            MorphTo::make('Initiator', 'initiator')->types([
                User::class,
            ])->searchable()->rules('required'),
            MorphTo::make('Message', 'message')->types([
                MessageTemplate::class, 
            ])->searchable()->rules('required'),
            Textarea::make('content')->rules('required'),
            Select::make('Type')->options([
                ModelsCommunication::STATUS_SENT => 'Sent',
            ])->rules('required'),
            Select::make('Communication Type')->options([
                ModelsCommunication::TYPE_SMS => 'SMS',
                ModelsCommunication::TYPE_EMAIL => 'Email',
            ])->rules('required'),
            
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
