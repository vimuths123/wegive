<?php

namespace App\Nova;

use App\Models\Communication;
use App\Models\CustomQuestion as ModelsCustomQuestion;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class CustomQuestion extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\CustomQuestion::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'title', 'name',
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
            Text::make('Name')->rules('required', 'max:191'),
            Text::make('Title')->rules('required', 'max:191'),
            Select::make('Input Type')->options([
                ModelsCustomQuestion::INPUT_TYPE_TEXT => 'Text',
                ModelsCustomQuestion::INPUT_TYPE_NUMBER => 'Number',
                ModelsCustomQuestion::INPUT_TYPE_CHECKBOX => 'Checkbox',
                ModelsCustomQuestion::INPUT_TYPE_SELECT => 'Select',
            ])->rules('required'),
            Select::make('Save Type')->options([
                ModelsCustomQuestion::CHECKOUT_ANSWER => 'Checkout Answer',
                ModelsCustomQuestion::CUSTOM_FIELD_ANSWER => 'Custom Field Answer'
            ])->rules('required')->default(ModelsCustomQuestion::CHECKOUT_ANSWER),
            // Select::make('Order')->options([
            // 
            // ])->rules('required'),
            BelongsTo::make('Checkout')->searchable()->rules('required'),
            Boolean::make('required')->default(1),
            Textarea::make('Answer Options')->rules('json', 'nullable'),
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
