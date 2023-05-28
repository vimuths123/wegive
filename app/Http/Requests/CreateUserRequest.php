<?php

namespace App\Http\Requests;

use App\Rules\UniqueUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::guest();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => ['string', 'required'],
            'last_name' => ['string', 'required'],
            'email' => ['email', 'required', 'unique:users', new UniqueUser()],
            'password' => ['string', 'min:6', 'required'],
            'device_name' => ['string', 'required'],
        ];
    }
}
