<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CustomQuestion extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    public const CHECKOUT_ANSWER = 1;
    public const CUSTOM_FIELD_ANSWER = 2;

    public const INPUT_TYPE_TEXT = 1;
    public const INPUT_TYPE_NUMBER = 2;
    public const INPUT_TYPE_CHECKBOX = 3;
    public const INPUT_TYPE_SELECT = 4;

    protected $fillable = ['*'];

    public function answers()
    {
        return $this->hasMany(CustomQuestionAnswers::class);
    }


    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }
}
